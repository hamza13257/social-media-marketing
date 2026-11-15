<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_default_organization(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Hamza',
            'email' => 'hamza@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization_name' => 'Marketing HQ',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user', 'tenant']);

        $this->assertDatabaseHas('users', ['email' => 'hamza@example.com']);
        $this->assertDatabaseHas('tenants', ['name' => 'Marketing HQ']);

        $tenant = Tenant::query()->where('name', 'Marketing HQ')->firstOrFail();
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => SubscriptionStatus::ManualPending->value,
        ]);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret-pass',
        ]);
        $tenant = Tenant::factory()->create();
        $tenant->users()->attach($user, ['role' => 'owner']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret-pass',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user', 'tenant']);
    }
}