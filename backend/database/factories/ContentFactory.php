<?php

namespace Database\Factories;

use App\Enums\ContentFormat;
use App\Enums\ContentPlatform;
use App\Enums\ContentStatus;
use App\Models\Brand;
use App\Models\Content;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'brand_id' => Brand::factory(),
            'created_by_user_id' => User::factory(),
            'topic' => fake()->sentence(4),
            'objective' => 'Engagement',
            'platform' => ContentPlatform::Instagram,
            'format' => ContentFormat::SingleImage,
            'language' => 'en',
            'caption' => null,
            'hashtags' => null,
            'status' => ContentStatus::Draft,
            'version' => 1,
        ];
    }
}