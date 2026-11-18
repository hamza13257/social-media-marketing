<?php

namespace App\Services\Content;

use App\Enums\ContentStatus;
use App\Models\Content;
use App\Models\WeekPlan;
use App\Services\Ai\AiCaptionService;
use App\Services\Ai\AiNotConfiguredException;
use App\Services\Ai\AiWeekPlanService;
use App\Services\Billing\PlanPublishingQuotaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WeekPlanGenerationService
{
    public function __construct(
        private WeekPlanSlotBuilder $slotBuilder,
        private AiWeekPlanService $weekPlanAi,
        private AiCaptionService $captionService,
        private PlanPublishingQuotaService $quotas,
    ) {}

    public function run(WeekPlan $plan): void
    {
        $plan->loadMissing('brand');
        $tenant = $plan->brand->tenant ?? \App\Models\Tenant::query()->findOrFail($plan->tenant_id);

        $plan->update([
            'status' => 'generating',
            'error_message' => null,
        ]);

        try {
            if (! $this->weekPlanAi->isConfigured()) {
                throw new AiNotConfiguredException('AI_API_KEY is not set.');
            }

            $weekStart = Carbon::parse($plan->week_start)->startOfDay();
            $slots = $this->slotBuilder->build($weekStart, $plan->rhythm ?? []);
            $enriched = $this->weekPlanAi->enrichSlots($plan->brand, $plan->brief, $weekStart, $slots);

            $generateCaptions = (bool) ($plan->rhythm['generate_captions'] ?? true);
            $createdIds = [];
            $warnings = [];

            DB::transaction(function () use ($plan, $weekStart, $enriched, $tenant, $generateCaptions, &$createdIds, &$warnings) {
                Content::query()
                    ->where('week_plan_id', $plan->id)
                    ->whereIn('status', [ContentStatus::Draft, ContentStatus::PendingApproval])
                    ->delete();

                foreach ($enriched as $row) {
                    $scheduledAt = $weekStart->copy()
                        ->addDays((int) $row['day_offset'])
                        ->setTimeFromTimeString((string) $row['time']);

                    try {
                        $this->quotas->assertWithinPublishingLimit(
                            $tenant,
                            $this->probeContent($plan->tenant_id, $row),
                            $scheduledAt
                        );
                    } catch (\InvalidArgumentException $exception) {
                        $warnings[] = $exception->getMessage();

                        continue;
                    }

                    $content = Content::query()->create([
                        'tenant_id' => $plan->tenant_id,
                        'brand_id' => $plan->brand_id,
                        'week_plan_id' => $plan->id,
                        'created_by_user_id' => $plan->created_by_user_id,
                        'topic' => $row['topic'],
                        'objective' => $row['objective'] ?? null,
                        'platform' => $row['platform'],
                        'format' => $row['format'],
                        'language' => $plan->brand->language ?? 'en',
                        'scheduled_at' => $scheduledAt,
                        'status' => ContentStatus::Draft,
                        'internal_notes' => $row['notes'] ?? null,
                    ]);

                    $createdIds[] = $content->id;

                    if ($generateCaptions) {
                        $this->generateCaptionFor($content, $warnings);
                    }
                }
            });

            $plan->update([
                'status' => 'ready_for_review',
                'generated_summary' => [
                    'content_count' => count($createdIds),
                    'content_ids' => $createdIds,
                    'warnings' => $warnings,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $exception) {
            $plan->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @param  array{format: string}  $row
     */
    private function probeContent(int $tenantId, array $row): Content
    {
        $c = new Content;
        $c->tenant_id = $tenantId;
        $c->format = $row['format'];

        return $c;
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function generateCaptionFor(Content $content, array &$warnings): void
    {
        try {
            $content->loadMissing('brand');
            $this->quotas->assertAiGenerationAllowed(
                $content->brand->tenant ?? \App\Models\Tenant::query()->findOrFail($content->tenant_id)
            );
            $content->transitionTo(ContentStatus::Generating);
            $content->save();

            $result = $this->captionService->generateForContent($content);
            $content->caption = $result['caption'];
            $content->hashtags = $result['hashtags'];
            $content->call_to_action = $result['call_to_action'];
            $content->post_structure = $result['post_structure'] ?? null;
            $content->ai_generated_at = now();
            $content->transitionTo(ContentStatus::PendingApproval);
            $content->version++;
            $content->save();

            foreach ($result['warnings'] ?? [] as $w) {
                $warnings[] = (string) $w;
            }
        } catch (\Throwable $exception) {
            $content->status = ContentStatus::Draft;
            $content->save();
            $warnings[] = "Caption failed for {$content->topic}: ".$exception->getMessage();
        }
    }
}