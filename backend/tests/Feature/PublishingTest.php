<?php

namespace Tests\Feature;

use App\Enums\ContentPlatform;
use App\Enums\ContentStatus;
use App\Enums\SocialConnectionStatus;
use App\Enums\SocialPlatform;
use App\Enums\TenantRole;
use App\Jobs\PublishContentJob;
use App\Models\Brand;
use App\Models\Content;
use App\Models\SocialAccount;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Publishing\PublishingOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_facebook_publish_sets_content_published(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'fb-post-123']),
        ]);

        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);
        $account = SocialAccount::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'platform' => SocialPlatform::FacebookPage,
            'external_id' => 'page-55',
            'access_token' => 'page-token',
            'connection_status' => SocialConnectionStatus::Connected,
        ]);
        $content = Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'platform' => ContentPlatform::Facebook,
            'status' => ContentStatus::Approved,
            'caption' => 'Hello Facebook',
        ]);

        app(PublishingOrchestrator::class)->publishContent($content->id, $account->id);

        $content->refresh();
        $this->assertSame(ContentStatus::Published, $content->status);
        $this->assertSame('fb-post-123', $content->external_post_id);
        $this->assertDatabaseHas('publishing_logs', [
            'content_id' => $content->id,
            'status' => 'success',
        ]);
    }

    public function test_publish_is_idempotent_per_content_version(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'fb-post-999']),
        ]);

        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);
        $account = SocialAccount::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'platform' => SocialPlatform::FacebookPage,
            'connection_status' => SocialConnectionStatus::Connected,
        ]);
        $content = Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'platform' => ContentPlatform::Facebook,
            'status' => ContentStatus::Approved,
            'version' => 2,
        ]);

        $orchestrator = app(PublishingOrchestrator::class);
        $orchestrator->publishContent($content->id, $account->id);

        $this->expectException(\RuntimeException::class);
        $orchestrator->publishContent($content->id, $account->id);
    }

    public function test_unapproved_content_cannot_be_queued_via_api(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);
        $owner = User::factory()->create();
        $tenant->users()->attach($owner, ['role' => TenantRole::Owner->value]);
        $content = Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'status' => ContentStatus::Draft,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson('/api/contents/'.$content->id.'/publish', [], [
            'X-Tenant-Id' => (string) $tenant->id,
        ])->assertForbidden();
    }

    public function test_publish_due_command_queues_jobs(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->create(['tenant_id' => $tenant->id]);
        Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'status' => ContentStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);
        Content::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'status' => ContentStatus::Scheduled,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->artisan('content:publish-due')->assertSuccessful();

        \Illuminate\Support\Facades\Queue::assertPushed(PublishContentJob::class, 1);
    }
}