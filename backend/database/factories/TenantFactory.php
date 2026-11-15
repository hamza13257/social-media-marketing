<?php

namespace Database\Factories;

use App\Enums\PaymentGatewayDriver;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Tenant $tenant): void {
            if (! Schema::hasTable('subscriptions')) {
                return;
            }

            if (SubscriptionPlan::query()->count() === 0) {
                (new SubscriptionPlanSeeder)->run();
            }

            $plan = SubscriptionPlan::query()->where('slug', 'agency')->first();
            if ($plan === null) {
                return;
            }

            Subscription::query()->firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Active,
                    'payment_gateway' => PaymentGatewayDriver::Manual,
                    'activated_at' => now(),
                ]
            );
        });
    }
}