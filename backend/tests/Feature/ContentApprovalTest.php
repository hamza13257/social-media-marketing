<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\TenantRole;
use App\Models\Brand;
use App\Models\Content;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContentApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_cannot_approve_content(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);
        $editor = User::factory()->create();
        $tenant->users()->attach($editor, ['role' => TenantRole::Editor->value]);
        $content = Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'created_by_user_id' => $editor->id,
            'status' => ContentStatus::PendingApproval,
        ]);

        Sanctum::actingAs($editor);

        $this->postJson('/api/contents/'.$content->id.'/approve', [], [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertForbidden();
    }

    public function test_owner_can_approve_pending_content(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);
        $owner = User::factory()->create();
        $tenant->users()->attach($owner, ['role' => TenantRole::Owner->value]);
        $content = Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'created_by_user_id' => $owner->id,
            'status' => ContentStatus::PendingApproval,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson('/api/contents/'.$content->id.'/approve', [
            'comment' => 'Looks good',
        ], [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('content_approval_logs', [
            'content_id' => $content->id,
            'decision' => 'approved',
        ]);
    }

    public function test_cannot_schedule_unapproved_content(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);
        $owner = User::factory()->create();
        $tenant->users()->attach($owner, ['role' => TenantRole::Owner->value]);
        $content = Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'created_by_user_id' => $owner->id,
            'status' => ContentStatus::Draft,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson('/api/contents/'.$content->id.'/schedule', [
            'scheduled_at' => now()->addDay()->toIso8601String(),
        ], [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertStatus(422);
    }

    public function test_ai_generate_updates_content_when_configured(): void
    {
        Config::set('ai.api_key', 'test-key');

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'caption' => 'Hello world caption',
                                'hashtags' => ['marketing', 'brand'],
                                'call_to_action' => 'Learn more',
                                'warnings' => [],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create();
        $tenant->users()->attach($user, ['role' => TenantRole::Editor->value]);
        $content = Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'created_by_user_id' => $user->id,
            'status' => ContentStatus::Draft,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/contents/'.$content->id.'/generate-caption', [], [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertOk()
            ->assertJsonPath('data.caption', 'Hello world caption')
            ->assertJsonPath('data.status', 'pending_approval');
    }
}