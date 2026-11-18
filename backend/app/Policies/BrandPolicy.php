<?php

namespace App\Policies;

use App\Enums\TenantRole;
use App\Models\Brand;
use App\Models\User;
use App\Services\Billing\SubscriptionQuotaService;
use App\Support\CurrentTenant;
use Illuminate\Auth\Access\Response;

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

    public function create(User $user): Response
    {
        if (! $this->memberWithBrandAccess()) {
            return Response::deny('You do not have permission to create brands in this organization.');
        }

        if (! config('billing.enforce_active_subscription')) {
            return Response::allow();
        }

        $tenant = $this->currentTenant->tenant();
        $quota = app(SubscriptionQuotaService::class);

        if (! $quota->subscriptionIsUsable($tenant)) {
            return Response::deny(
                'Subscription is not active. Open Billing to request a plan, or ask a super-admin to activate your workspace in /admin.'
            );
        }

        if (! $quota->withinLimit($tenant, 'brands', $tenant->brands()->count())) {
            return Response::deny('Brand limit reached for your current plan. Upgrade billing or remove a brand.');
        }

        return Response::allow();
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