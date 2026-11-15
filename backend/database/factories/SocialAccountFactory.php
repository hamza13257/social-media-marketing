<?php

namespace Database\Factories;

use App\Enums\SocialConnectionStatus;
use App\Enums\SocialPlatform;
use App\Models\Brand;
use App\Models\SocialAccount;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'brand_id' => null,
            'platform' => SocialPlatform::FacebookPage,
            'external_id' => (string) fake()->unique()->numerify('##########'),
            'name' => fake()->company().' Page',
            'username' => null,
            'profile_picture_url' => null,
            'access_token' => 'factory-token',
            'token_expires_at' => now()->addDays(50),
            'scopes' => ['pages_show_list'],
            'connection_status' => SocialConnectionStatus::Connected,
            'metadata' => [],
            'connected_at' => now(),
        ];
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn () => [
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
        ]);
    }

    public function instagram(): static
    {
        return $this->state(fn () => [
            'platform' => SocialPlatform::InstagramProfessional,
            'name' => '@'.fake()->userName(),
            'username' => fake()->userName(),
        ]);
    }
}