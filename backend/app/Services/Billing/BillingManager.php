<?php

namespace App\Services\Billing;

use App\Enums\PaymentGatewayDriver;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;

class BillingManager
{
    public function gateway(): PaymentGateway
    {
        return match (config('billing.gateway')) {
            PaymentGatewayDriver::Stripe => app(StripePaymentGateway::class),
            default => app(ManualPaymentGateway::class),
        };
    }

    public function requestPlan(Tenant $tenant, string $planSlug): array
    {
        $plan = SubscriptionPlan::query()->where('slug', $planSlug)->firstOrFail();

        return $this->gateway()->beginCheckout($tenant, $plan);
    }
}