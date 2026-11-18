<?php

namespace App\Http\Controllers\Api;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\ApprovalActionRequest;
use App\Http\Requests\Content\PublishContentRequest;
use App\Http\Requests\Content\ScheduleContentRequest;
use App\Http\Requests\Content\StoreContentRequest;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Http\Resources\ContentResource;
use App\Models\Content;
use App\Services\Ai\AiCaptionService;
use App\Services\Ai\AiNotConfiguredException;
use App\Services\Content\ContentApprovalService;
use App\Jobs\PublishContentJob;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    public function __construct(
        private CurrentTenant $currentTenant,
        private ContentApprovalService $approvalService,
        private AiCaptionService $aiCaptionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        $contents = Content::query()
            ->with(['brand:id,name', 'author:id,name'])
            ->where('tenant_id', $this->currentTenant->id())
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('pending_only'), fn ($q) => $q->where('status', ContentStatus::PendingApproval))
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'data' => ContentResource::collection($contents),
            'meta' => [
                'ai_configured' => $this->aiCaptionService->isConfigured(),
            ],
        ]);
    }

    public function store(StoreContentRequest $request): JsonResponse
    {
        $this->authorize('create', Content::class);

        $content = Content::query()->create([
            ...$request->validated(),
            'tenant_id' => $this->currentTenant->id(),
            'created_by_user_id' => $request->user()->id,
            'language' => $request->validated('language') ?? 'en',
            'status' => ContentStatus::Draft,
        ]);

        $content->load(['brand:id,name', 'author:id,name']);

        return response()->json(['data' => new ContentResource($content)], 201);
    }

    public function show(Content $content): JsonResponse
    {
        $this->authorize('view', $content);

        $content->load(['brand:id,name', 'author:id,name', 'approvalLogs.user:id,name']);

        return response()->json(['data' => new ContentResource($content)]);
    }

    public function update(UpdateContentRequest $request, Content $content): JsonResponse
    {
        $this->authorize('update', $content);

        $content->fill($request->validated());
        if ($content->isDirty()) {
            $content->version++;
        }
        $content->save();
        $content->load(['brand:id,name', 'author:id,name']);

        return response()->json(['data' => new ContentResource($content)]);
    }

    public function destroy(Content $content): JsonResponse
    {
        $this->authorize('delete', $content);

        $content->delete();

        return response()->json(null, 204);
    }

    public function submit(ApprovalActionRequest $request, Content $content): JsonResponse
    {
        $this->authorize('submit', $content);

        if (! in_array($content->status, [ContentStatus::Draft, ContentStatus::ChangesRequested], true)) {
            return response()->json(['message' => 'Only draft or changed content can be submitted.'], 422);
        }

        $content = $this->approvalService->submit($content, $request->user(), $request->validated('comment'));

        return response()->json(['data' => new ContentResource($content)]);
    }

    public function approve(ApprovalActionRequest $request, Content $content): JsonResponse
    {
        $this->authorize('approve', $content);

        $content = $this->approvalService->approve($content, $request->user(), $request->validated('comment'));

        return response()->json(['data' => new ContentResource($content)]);
    }

    public function reject(ApprovalActionRequest $request, Content $content): JsonResponse
    {
        $this->authorize('approve', $content);

        $content = $this->approvalService->reject($content, $request->user(), $request->validated('comment'));

        return response()->json(['data' => new ContentResource($content)]);
    }

    public function requestChanges(ApprovalActionRequest $request, Content $content): JsonResponse
    {
        $this->authorize('approve', $content);

        $content = $this->approvalService->requestChanges($content, $request->user(), $request->validated('comment'));

        return response()->json(['data' => new ContentResource($content)]);
    }

    public function schedule(ScheduleContentRequest $request, Content $content): JsonResponse
    {
        $this->authorize('schedule', $content);

        try {
            $content = $this->approvalService->schedule(
                $content,
                $request->user(),
                $request->date('scheduled_at'),
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => new ContentResource($content)]);
    }

    public function publish(PublishContentRequest $request, Content $content): JsonResponse
    {
        $this->authorize('publish', $content);

        PublishContentJob::dispatch(
            $content->id,
            $request->validated('social_account_id'),
            'content:'.$content->id.':v'.$content->version,
        );

        return response()->json([
            'message' => 'Publishing has been queued.',
            'content_id' => $content->id,
        ], 202);
    }

    public function generateCaption(Content $content): JsonResponse
    {
        $this->authorize('generate', $content);

        try {
            $content = DB::transaction(function () use ($content) {
                $from = $content->status;
                $content->transitionTo(ContentStatus::Generating);
                $content->save();

                $result = $this->aiCaptionService->generateForContent($content);

                $content->caption = $result['caption'];
                $content->hashtags = $result['hashtags'];
                $content->call_to_action = $result['call_to_action'];
                $content->transitionTo(ContentStatus::PendingApproval);
                $content->version++;
                $content->save();

                return [$content->fresh(['brand', 'author']), $result['warnings'] ?? []];
            });
        } catch (AiNotConfiguredException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (\Throwable $exception) {
            $content->status = ContentStatus::Draft;
            $content->save();

            $message = $exception->getMessage();
            if (! str_contains($message, 'AI provider')) {
                $message = 'AI generation failed. '.$message;
            }

            return response()->json([
                'message' => $message,
                'hint' => 'Set AI_API_KEY (Groq), AI_BASE_URL=https://api.groq.com/openai/v1, AI_MODEL=llama-3.3-70b-versatile, then php artisan config:clear.',
            ], 502);
        }

        /** @var array{0: Content, 1: array<int, string>} $content */
        [$fresh, $warnings] = $content;

        return response()->json([
            'data' => new ContentResource($fresh),
            'warnings' => $warnings,
        ]);
    }
}