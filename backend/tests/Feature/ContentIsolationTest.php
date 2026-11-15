<?php

namespace Tests\Feature;

use App\Enums\TenantRole;
use App\Models\Brand;
use App\Models\Content;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_foreign_tenant_content(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->create();
        $tenantA->users()->attach($user, ['role' => TenantRole::Owner->value]);
        $brandB = Brand::factory()->create(['tenant_id' => $tenantB->id]);
        $foreign = Content::factory()->create([
            'tenant_id' => $tenantB->id,
            'brand_id' => $brandB->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/contents/'.$foreign->id, [
            'X-Tenant-Id' => (string) $tenantA->id,
        ])->assertForbidden();
    }
}