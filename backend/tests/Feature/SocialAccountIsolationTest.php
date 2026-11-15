<?php

namespace Tests\Feature;

use App\Enums\TenantRole;
use App\Models\SocialAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SocialAccountIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_foreign_tenant_social_account(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->create();
        $tenantA->users()->attach($user, ['role' => TenantRole::Owner->value]);

        $foreign = SocialAccount::factory()->create(['tenant_id' => $tenantB->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/social-accounts/'.$foreign->id, [
            'X-Tenant-Id' => (string) $tenantA->id,
        ])->assertForbidden();
    }

    public function test_list_only_returns_current_tenant_accounts(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        $user = User::factory()->create();
        $tenantA->users()->attach($user, ['role' => TenantRole::Owner->value]);

        $mine = SocialAccount::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Mine']);
        SocialAccount::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Theirs']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/social-accounts', [
            'X-Tenant-Id' => (string) $tenantA->id,
        ])->assertOk();

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertSame(['Mine'], $names);
        $this->assertSame($mine->id, $response->json('data.0.id'));
    }
}