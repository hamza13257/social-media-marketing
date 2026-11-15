<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantRole;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_owner_can_request_plan_and_admin_can_activate(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $tenant->users()->attach($user, ['role' => TenantRole::Owner->value]);

        $starter = SubscriptionPlan::query()->where('slug', 'starter')->firstOrFail();
        Subscription::query()->where('tenant_id', $tenant->id)->delete();
        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $starter->id,
            'status' => SubscriptionStatus::ManualPending,
            'payment_gateway' => 'manual',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/billing/request-plan', [
            'plan_slug' => 'pro',
        ], [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertOk()
            ->assertJsonPath('subscription.status', SubscriptionStatus::ManualPending->value);

        $pending = Subscription::query()->where('tenant_id', $tenant->id)->firstOrFail();

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/subscriptions/'.$pending->id.'/activate', [
            'notes' => 'Bank transfer ref #123',
        ])->assertOk()
            ->assertJsonPath('data.status', SubscriptionStatus::Active->value);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $pending->id,
            'status' => SubscriptionStatus::Active->value,
        ]);
    }

    public function test_non_super_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        Sanctum::actingAs($user);

        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }
}