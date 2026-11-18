<?php

namespace App\Services\Billing;

use App\Enums\ContentFormat;
use App\Enums\ContentStatus;
use App\Models\Content;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlanPublishingQuotaService
{
    public const METRIC_POSTS_PER_DAY = 'posts_per_day';

    public const METRIC_REELS_PER_WEEK = 'reels_per_week';

    public const METRIC_STORIES_PER_DAY = 'stories_per_day';

    public function __construct(private SubscriptionQuotaService $quotas) {}

    public function metricForFormat(ContentFormat $format): ?string
    {
        return match ($format) {
            ContentFormat::Reel => self::METRIC_REELS_PER_WEEK,
            ContentFormat::Story => self::METRIC_STORIES_PER_DAY,
            ContentFormat::SingleImage, ContentFormat::Carousel, ContentFormat::Article => self::METRIC_POSTS_PER_DAY,
        };
    }

  /**
   * @param  array<ContentFormat|string>  $formats
   */
    public function countForMetric(Tenant $tenant, string $metric, Carbon $reference, ?int $excludeContentId = null): int
    {
        $formats = $this->formatsForMetric($metric);
        if ($formats === []) {
            return 0;
        }

        $query = Content::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('format', $formats)
            ->whereNotIn('status', [ContentStatus::Draft, ContentStatus::Rejected]);

        if ($excludeContentId !== null) {
            $query->where('id', '!=', $excludeContentId);
        }

        if ($metric === self::METRIC_REELS_PER_WEEK) {
            $start = $reference->copy()->startOfWeek();
            $end = $reference->copy()->endOfWeek();

            return $query->whereRaw(
                'COALESCE(scheduled_at, published_at) BETWEEN ? AND ?',
                [$start, $end]
            )->count();
        }

        $day = $reference->toDateString();

        return $query->whereRaw('DATE(COALESCE(scheduled_at, published_at)) = ?', [$day])->count();
    }

    public function assertWithinPublishingLimit(Tenant $tenant, Content $content, Carbon $slot): void
    {
        if (! config('billing.enforce_active_subscription')) {
            return;
        }

        if (! $this->quotas->subscriptionIsUsable($tenant)) {
            throw new \InvalidArgumentException('Subscription is not active. Open Billing or ask an admin to activate your plan.');
        }

        $metric = $this->metricForFormat($content->format);
        if ($metric === null) {
            return;
        }

        $subscription = $tenant->subscription()->with('plan')->first();
        $limit = $subscription?->plan?->limit($metric);

        if ($limit === null) {
            return;
        }

        $count = $this->countForMetric($tenant, $metric, $slot, $content->id);

        if ($count >= $limit) {
            throw new \InvalidArgumentException(
                "Plan limit reached for {$metric} (max {$limit}). Upgrade your plan or choose another time slot."
            );
        }
    }

    public function assertAiGenerationAllowed(Tenant $tenant): void
    {
        if (! config('billing.enforce_active_subscription')) {
            return;
        }

        if (! $this->quotas->subscriptionIsUsable($tenant)) {
            throw new \InvalidArgumentException('Subscription is not active.');
        }

        $limit = $tenant->subscription()->with('plan')->first()?->plan?->limit('ai_generations_month');
        if ($limit === null) {
            return;
        }

        $count = $this->aiGenerationsThisMonth($tenant);
        if ($count >= $limit) {
            throw new \InvalidArgumentException(
                "AI generation limit reached for this month ({$limit}). Upgrade your plan or wait until next month."
            );
        }
    }

    public function aiGenerationsThisMonth(Tenant $tenant): int
    {
        return Content::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('ai_generated_at')
            ->where('ai_generated_at', '>=', now()->startOfMonth())
            ->count();
    }

    /**
     * @return array{limits: array<string, int|null>, usage: array<string, int>, content: array<string, int>}
     */
    public function snapshot(Tenant $tenant): array
    {
        $tenant->loadMissing('subscription.plan');

        $limits = $tenant->subscription?->plan?->limits ?? [];

        $usage = [
            'posts_today' => $this->countForMetric($tenant, self::METRIC_POSTS_PER_DAY, now()),
            'reels_this_week' => $this->countForMetric($tenant, self::METRIC_REELS_PER_WEEK, now()),
            'stories_today' => $this->countForMetric($tenant, self::METRIC_STORIES_PER_DAY, now()),
            'ai_generations_month' => $this->aiGenerationsThisMonth($tenant),
            'brands' => $tenant->brands()->count(),
            'social_accounts' => $tenant->socialAccounts()->count(),
        ];

        $statusCounts = Content::query()
            ->where('tenant_id', $tenant->id)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $upcomingScheduled = Content::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', ContentStatus::Scheduled)
            ->where('scheduled_at', '>=', now())
            ->count();

        return [
            'plan_name' => $tenant->subscription?->plan?->name,
            'plan_slug' => $tenant->subscription?->plan?->slug,
            'subscription_status' => $tenant->subscription?->status?->value,
            'limits' => $limits,
            'usage' => $usage,
            'content' => [
                'by_status' => $statusCounts,
                'upcoming_scheduled' => $upcomingScheduled,
            ],
        ];
    }

    /**
     * @return array<int, ContentFormat>
     */
    private function formatsForMetric(string $metric): array
    {
        return match ($metric) {
            self::METRIC_POSTS_PER_DAY => [
                ContentFormat::SingleImage,
                ContentFormat::Carousel,
                ContentFormat::Article,
            ],
            self::METRIC_REELS_PER_WEEK => [ContentFormat::Reel],
            self::METRIC_STORIES_PER_DAY => [ContentFormat::Story],
            default => [],
        };
    }
}