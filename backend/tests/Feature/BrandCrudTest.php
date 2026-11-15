<?php

namespace Tests\Feature;

use App\Enums\TenantRole;
use App\Models\Brand;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrandCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsTenantMember(TenantRole $role = TenantRole::Owner): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $tenant->users()->attach($user, ['role' => $role->value]);
        Sanctum::actingAs($user);

        return [$user, $tenant];
    }

    public function test_owner_can_create_brand(): void
    {
        [, $tenant] = $this->actingAsTenantMember();

        $response = $this->postJson('/api/brands', [
            'name' => 'SignalQ Trading',
            'description' => 'Trading education brand',
            'brand_colors' => ['#1e40af', '#2563eb'],
        ], [
            'X-Tenant-Id' => (string) $tenant->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'SignalQ Trading')
            ->assertJsonPath('data.slug', 'signalq-trading');

        $this->assertDatabaseHas('brands', [
            'tenant_id' => $tenant->id,
            'name' => 'SignalQ Trading',
        ]);
    }

    public function test_editor_cannot_delete_brand(): void
    {
        [, $tenant] = $this->actingAsTenantMember(TenantRole::Editor);
        $brand = Brand::factory()->for($tenant)->create();

        $this->deleteJson('/api/brands/'.$brand->id, [], [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertForbidden();
    }

    public function test_owner_can_archive_brand(): void
    {
        [, $tenant] = $this->actingAsTenantMember();
        $brand = Brand::factory()->for($tenant)->create();

        $this->patchJson('/api/brands/'.$brand->id, [
            'archived' => true,
        ], [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.archived_at', fn ($value) => $value !== null);
    }
}