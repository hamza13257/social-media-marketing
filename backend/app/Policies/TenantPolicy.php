<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;

class TenantPolicy
{
    public function __construct(private CurrentTenant $currentTenant) {}

    public function manageBilling(User $user, Tenant $tenant): bool
    {
        if (! $this->currentTenant->isSet() || $this->currentTenant->id() !== $tenant->getKey()) {
            return false;
        }

        return $this->currentTenant->role()->canManageTenant();
    }
}