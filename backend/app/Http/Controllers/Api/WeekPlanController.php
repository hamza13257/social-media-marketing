<?php

namespace App\Http\Controllers\Api;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContentResource;
use App\Http\Resources\WeekPlanResource;
use App\Jobs\GenerateWeekPlanJob;
use App\Models\Brand;
use App\Models\WeekPlan;
use App\Services\Ai\AiWeekPlanService;
use App\Services\Content\ContentApprovalService;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeekPlanController extends Controller
{
    public function __construct(
        private CurrentTenant $currentTenant,
        private AiWeekPlanService $weekPlanAi,
        private ContentApprovalService $approvalService,
    ) {}

    public function index(): JsonResponse
    {
        $plans = WeekPlan::query()
            ->with(['brand:id,name', 'author:id,name'])
            ->withCount('contents')
            ->where('tenant_id', $this->currentTenant->id())
            ->orderByDesc('week_start')
            ->limit(20)
            ->get();

        return response()->json(['data' => WeekPlanResource::collection($plans)]);
    }

    public function show(WeekPlan $weekPlan): JsonResponse
    {
        $this->assertTenant($weekPlan);

        $weekPlan->load(['brand:id,name', 'author:id,name']);
        $weekPlan->loadCount('contents');

        $contents = $weekPlan->contents()
            ->with(['brand:id,name'])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'data' => new WeekPlanResource($weekPlan),
            'contents' => ContentResource::collection($contents),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'week_start' => ['required', 'date'],
            'brief' => ['required', 'string', 'max:5000'],
            'rhythm' => ['required', 'array'],
            'rhythm.posts_per_day' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'rhythm.reels_per_week' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'rhythm.stories_per_day' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'rhythm.platforms' => ['sometimes', 'array'],
            'rhythm.platforms.*' => ['string', 'in:instagram,facebook'],
            'rhythm.generate_captions' => ['sometimes', 'boolean'],
        ]);

        $brand = Brand::query()->findOrFail($data['brand_id']);
        if ((int) $brand->tenant_id !== $this->currentTenant->id()) {
            abort(403);
        }

        $plan = WeekPlan::query()->updateOrCreate(
            [
                'tenant_id' => $this->currentTenant->id(),
                'brand_id' => $data['brand_id'],
                'week_start' => $data['week_start'],
            ],
            [
                'created_by_user_id' => $request->user()->id,
                'brief' => $data['brief'],
                'rhythm' => $data['rhythm'],
                'status' => 'draft',
                'error_message' => null,
            ]
        );

        $plan->load(['brand:id,name', 'author:id,name']);

        return response()->json([
            'data' => new WeekPlanResource($plan),
            'message' => 'Week plan saved.',
        ], 201);
    }

    public function generate(Request $request, WeekPlan $weekPlan): JsonResponse
    {
        $this->assertTenant($weekPlan);

        if (! $this->weekPlanAi->isConfigured()) {
            return response()->json([
                'message' => 'AI is not configured.',
                'hint' => 'Set AI_API_KEY and run php artisan config:clear',
            ], 503);
        }

        if (in_array($weekPlan->status, ['generating'], true)) {
            return response()->json(['message' => 'Generation already in progress.'], 409);
        }

        $weekPlan->update(['status' => 'queued']);
        GenerateWeekPlanJob::dispatch($weekPlan->id);

        return response()->json([
            'message' => 'Week generation queued. Run php artisan queue:work.',
            'week_plan_id' => $weekPlan->id,
        ], 202);
    }

    public function approveAll(Request $request, WeekPlan $weekPlan): JsonResponse
    {
        $this->assertTenant($weekPlan);
        $user = $request->user();

        $contents = $weekPlan->contents()
            ->whereIn('status', [ContentStatus::Draft, ContentStatus::PendingApproval])
            ->orderBy('scheduled_at')
            ->get();

        $approved = 0;
        foreach ($contents as $content) {
            if ($content->status === ContentStatus::Draft) {
                $content = $this->approvalService->submit($content, $user, 'Week plan batch');
            }

            if ($content->status === ContentStatus::PendingApproval) {
                $content = $this->approvalService->approve($content, $user, 'Week plan batch approve');

                if ($content->scheduled_at !== null) {
                    $content = $this->approvalService->schedule(
                        $content,
                        $user,
                        $content->scheduled_at,
                    );
                }

                $approved++;
            }
        }

        $weekPlan->update(['status' => 'active']);

        return response()->json([
            'message' => "Approved and scheduled {$approved} posts.",
            'approved_count' => $approved,
        ]);
    }

    private function assertTenant(WeekPlan $weekPlan): void
    {
        if ((int) $weekPlan->tenant_id !== $this->currentTenant->id()) {
            abort(404);
        }
    }
}