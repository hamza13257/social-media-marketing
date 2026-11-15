<?php

namespace App\Services\Billing;

use App\Models\Tenant;

class SubscriptionQuotaService
{
    public function subscriptionIsUsable(Tenant $tenant): bool
    {
        $subscription = $tenant->subscription()->with('plan')->first();

        return $subscription !== null && $subscription->status->isUsable();
    }

    public function withinLimit(Tenant $tenant, string $metric, int $currentCount): bool
    {
        $subscription = $tenant->subscription()->with('plan')->first();

        if ($subscription === null || ! $subscription->status->isUsable()) {
            return false;
        }

        $limit = $subscription->plan->limit($metric);

        if ($limit === null) {
            return true;
        }

        return $currentCount < $limit;
    }
}