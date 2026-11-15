<?php

namespace Tests\Feature;

use App\Enums\TenantRole;
use App\Models\SocialAccount;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MetaOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('meta.app_id', 'test-app-id');
        Config::set('meta.app_secret', 'test-secret');
        Config::set('meta.redirect_uri', 'http://localhost/api/meta/oauth/callback');
        Config::set('meta.frontend_callback', 'http://localhost:3000/social-accounts');
        Config::set('meta.graph_version', 'v21.0');
    }

    public function test_owner_can_request_oauth_url(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $tenant->users()->attach($user, ['role' => TenantRole::Owner->value]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/meta/oauth/url', [
            'X-Tenant-Id' => (string) $tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['authorize_url', 'state']);
        $this->assertStringContainsString('facebook.com', $response->json('authorize_url'));
    }

    public function test_callback_stores_discovered_accounts(): void
    {
        Http::fake([
            'graph.facebook.com/*/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-token', 'expires_in' => 3600])
                ->push(['access_token' => 'long-token', 'expires_in' => 5184000]),
            'graph.facebook.com/*/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => 'page-100',
                        'name' => 'Demo Page',
                        'access_token' => 'page-token',
                        'instagram_business_account' => ['id' => 'ig-200'],
                    ],
                ],
            ]),
            'graph.facebook.com/*/ig-200*' => Http::response([
                'id' => 'ig-200',
                'username' => 'demo_brand',
                'name' => 'Demo Brand',
                'profile_picture_url' => 'https://example.com/pic.jpg',
            ]),
        ]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $state = 'test-state-123';

        Cache::put('meta_oauth_state:'.$state, [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'brand_id' => null,
        ], now()->addMinutes(10));

        $response = $this->get('/api/meta/oauth/callback?code=abc&state='.$state);

        $response->assertRedirect();
        $this->assertStringContainsString('connected=2', $response->headers->get('Location'));

        $this->assertDatabaseHas('social_accounts', [
            'tenant_id' => $tenant->id,
            'external_id' => 'page-100',
            'platform' => 'facebook_page',
        ]);
        $this->assertDatabaseHas('social_accounts', [
            'tenant_id' => $tenant->id,
            'external_id' => 'ig-200',
            'platform' => 'instagram_professional',
        ]);

        $account = SocialAccount::query()->where('external_id', 'page-100')->first();
        $this->assertNotNull($account);
        $this->assertSame('page-token', $account->access_token);
    }

    public function test_editor_cannot_start_oauth(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $tenant->users()->attach($user, ['role' => TenantRole::Editor->value]);
        Sanctum::actingAs($user);

        $this->getJson('/api/meta/oauth/url', [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertForbidden();
    }
}