<?php

namespace Tests\Feature;

use App\Enums\TenantRole;
use App\Models\Brand;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_tenants_brand(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $tenantA->users()->attach($userA, ['role' => TenantRole::Owner->value]);
        $tenantB->users()->attach($userB, ['role' => TenantRole::Owner->value]);

        $foreignBrand = Brand::factory()->for($tenantB)->create();

        Sanctum::actingAs($userA);

        $this->getJson('/api/brands/'.$foreignBrand->id, [
            'X-Tenant-Id' => (string) $tenantA->id,
        ])->assertForbidden();
    }

    public function test_user_cannot_list_brands_using_another_tenants_header(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user = User::factory()->create();
        $tenantA->users()->attach($user, ['role' => TenantRole::Owner->value]);

        Brand::factory()->for($tenantB)->count(2)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/brands', [
            'X-Tenant-Id' => (string) $tenantB->id,
        ]);

        $response->assertForbidden();
    }

    public function test_brand_list_only_returns_current_tenant_records(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $user = User::factory()->create();
        $tenantA->users()->attach($user, ['role' => TenantRole::Owner->value]);
        $tenantB->users()->attach($user, ['role' => TenantRole::Editor->value]);

        $brandA = Brand::factory()->for($tenantA)->create(['name' => 'Alpha Brand']);
        Brand::factory()->for($tenantB)->create(['name' => 'Beta Brand']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/brands', [
            'X-Tenant-Id' => (string) $tenantA->id,
        ])->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$brandA->id], $ids);
    }
}