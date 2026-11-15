<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\User;
use App\Support\CurrentTenant;

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

    public function connect(User $user): bool
    {
        return $this->currentTenant->isSet()
            && $this->currentTenant->role()->canManageSocialAccounts();
    }

    private function sameTenant(SocialAccount $socialAccount): bool
    {
        return $this->currentTenant->isSet()
            && (int) $socialAccount->tenant_id === $this->currentTenant->id();
    }
}