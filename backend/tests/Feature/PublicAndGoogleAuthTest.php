<?php

namespace Tests\Feature;

use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAndGoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_public_bootstrap_lists_plans_and_features(): void
    {
        $this->getJson('/api/public/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.plans.0.slug', 'starter')
            ->assertJsonStructure(['data' => ['features', 'google_auth_enabled']]);
    }

    public function test_google_url_returns_service_unavailable_without_credentials(): void
    {
        config(['services.google.client_id' => null]);

        $this->getJson('/api/auth/google/url')->assertStatus(503);
    }
}