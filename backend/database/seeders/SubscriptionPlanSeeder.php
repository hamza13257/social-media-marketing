<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'description' => 'Personal brands and light publishing.',
                'price_cents' => 2900,
                'limits' => [
                    'brands' => 3,
                    'social_accounts' => 4,
                    'team_members' => 2,
                    'ai_generations_month' => 200,
                    'storage_mb' => 1024,
                ],
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'description' => 'Growing businesses and daily publishing.',
                'price_cents' => 7900,
                'limits' => [
                    'brands' => 10,
                    'social_accounts' => 20,
                    'team_members' => 10,
                    'ai_generations_month' => 2000,
                    'storage_mb' => 10240,
                ],
            ],
            [
                'slug' => 'agency',
                'name' => 'Agency',
                'description' => 'Multiple clients and higher limits.',
                'price_cents' => 19900,
                'limits' => [
                    'brands' => null,
                    'social_accounts' => null,
                    'team_members' => null,
                    'ai_generations_month' => null,
                    'storage_mb' => null,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan + ['is_public' => true, 'currency' => 'USD']
            );
        }
    }
}