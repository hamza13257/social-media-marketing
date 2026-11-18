<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Billing\SubscriptionQuotaService;
use App\Support\CurrentTenant;
use Illuminate\Auth\Access\Response;

class SocialAccountPolicy
{
    public function __construct(private CurrentTenant $currentTenant) {}

    public function viewAny(User $user): bool
    {
        return $this->currentTenant->isSet()
            && $this->currentTenant->role()->canViewSocialAccounts();
    }

    public function view(User $user, SocialAccount $socialAccount): bool
    {
        return $this->sameTenant($socialAccount)
            && $this->currentTenant->role()->canViewSocialAccounts();
    }

    public function update(User $user, SocialAccount $socialAccount): bool
    {
        return $this->sameTenant($socialAccount)
            && $this->currentTenant->role()->canManageSocialAccounts();
    }

    public function delete(User $user, SocialAccount $socialAccount): bool
    {
        return $this->sameTenant($socialAccount)
            && $this->currentTenant->role()->canManageSocialAccounts();
    }

    public function connect(User $user): Response
    {
        if (! $this->currentTenant->isSet() || ! $this->currentTenant->role()->canManageSocialAccounts()) {
            return Response::deny('You do not have permission to connect social accounts.');
        }

        if (! config('billing.enforce_active_subscription')) {
            return Response::allow();
        }

        $tenant = $this->currentTenant->tenant();
        $quota = app(SubscriptionQuotaService::class);

        if (! $quota->subscriptionIsUsable($tenant)) {
            return Response::deny('Subscription is not active.');
        }

        if (! $quota->withinLimit($tenant, 'social_accounts', $tenant->socialAccounts()->count())) {
            return Response::deny(
                'Social account limit reached for your plan. Disconnect an account or upgrade billing.'
            );
        }

        return Response::allow();
    }

    private function sameTenant(SocialAccount $socialAccount): bool
    {
        return $this->currentTenant->isSet()
            && (int) $socialAccount->tenant_id === $this->currentTenant->id();
    }
}