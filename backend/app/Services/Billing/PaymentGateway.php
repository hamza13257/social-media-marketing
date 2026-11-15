<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;

interface PaymentGateway
{
    public function driver(): string;

    /**
     * @return array{redirect_url?: string, client_secret?: string, instructions?: string}
     */
    public function beginCheckout(Tenant $tenant, SubscriptionPlan $plan): array;

    public function markManualPaymentReceived(
        Subscription $subscription,
        string $notes,
        int $activatedByUserId,
    ): Subscription;
}