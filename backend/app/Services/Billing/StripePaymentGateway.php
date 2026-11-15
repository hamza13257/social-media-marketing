<?php

namespace App\Services\Billing;

use App\Enums\PaymentGatewayDriver;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;

/**
 * Placeholder Stripe gateway — wire your keys when you choose stripe as PAYMENT_GATEWAY.
 */
class StripePaymentGateway implements PaymentGateway
{
    public function driver(): string
    {
        return PaymentGatewayDriver::Stripe->value;
    }

    public function beginCheckout(Tenant $tenant, SubscriptionPlan $plan): array
    {
        if (! filled(config('billing.stripe.secret'))) {
            throw new \RuntimeException('Stripe is not configured. Set STRIPE_SECRET or use PAYMENT_GATEWAY=manual.');
        }

        Subscription::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::ManualPending,
                'payment_gateway' => PaymentGatewayDriver::Stripe,
            ]
        );

        // Integrate Stripe Checkout Session here when you provide STRIPE_* keys.
        return [
            'instructions' => 'Stripe checkout will be available once STRIPE_SECRET and price IDs are configured.',
        ];
    }

    public function markManualPaymentReceived(
        Subscription $subscription,
        string $notes,
        int $activatedByUserId,
    ): Subscription {
        return app(ManualPaymentGateway::class)->markManualPaymentReceived(
            $subscription,
            $notes,
            $activatedByUserId,
        );
    }
}