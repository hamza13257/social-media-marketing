<?php

namespace Tests\Feature;

use App\Enums\ContentFormat;
use App\Enums\ContentStatus;
use App\Models\Brand;
use App\Models\Content;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\PlanPublishingQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPublishingQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_scheduled_post_toward_daily_limit(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);
        $plan = SubscriptionPlan::query()->where('slug', 'starter')->firstOrFail();
        $plan->limits = array_merge($plan->limits ?? [], ['posts_per_day' => 1]);
        $plan->save();

        $tenant = Tenant::factory()->create();
        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'payment_gateway' => 'manual',
        ]);

        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);

        Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'format' => ContentFormat::SingleImage,
            'status' => ContentStatus::Scheduled,
            'scheduled_at' => now(),
        ]);

        $service = app(PlanPublishingQuotaService::class);
        $this->assertSame(1, $service->countForMetric($tenant, PlanPublishingQuotaService::METRIC_POSTS_PER_DAY, now()));

        $second = Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'format' => ContentFormat::SingleImage,
            'status' => ContentStatus::Approved,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $service->assertWithinPublishingLimit($tenant, $second, now());
    }
}