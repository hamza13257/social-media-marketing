<?php

namespace App\Policies;

use App\Enums\TenantRole;
use App\Models\Brand;
use App\Models\User;
use App\Support\CurrentTenant;

class BrandPolicy
{
    public function __construct(private CurrentTenant $currentTenant) {}

    public function viewAny(User $user): bool
    {
        return $this->memberWithBrandAccess();
    }

    public function view(User $user, Brand $brand): bool
    {
        return $this->sameTenant($brand) && $this->memberWithBrandAccess();
    }

    public function create(User $user): bool
    {
        return $this->memberWithBrandAccess();
    }

    public function update(User $user, Brand $brand): bool
    {
        return $this->sameTenant($brand) && $this->memberWithBrandAccess();
    }

    public function delete(User $user, Brand $brand): bool
    {
        if (! $this->sameTenant($brand)) {
            return false;
        }

        $role = $this->currentTenant->isSet()
            ? $this->currentTenant->role()
            : $user->roleOnTenant($brand->tenant);

        return $role?->canDeleteBrands() ?? false;
    }

    private function memberWithBrandAccess(): bool
    {
        if (! $this->currentTenant->isSet()) {
            return false;
        }

        return $this->currentTenant->role()->canManageBrands();
    }

    private function sameTenant(Brand $brand): bool
    {
        if (! $this->currentTenant->isSet()) {
            return false;
        }

        return (int) $brand->tenant_id === $this->currentTenant->id();
    }
}