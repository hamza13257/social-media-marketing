<?php

namespace App\Support;

use App\Enums\TenantRole;
use App\Models\Tenant;

class CurrentTenant
{
    private ?Tenant $tenant = null;

    private ?TenantRole $role = null;

    public function set(Tenant $tenant, TenantRole $role): void
    {
        $this->tenant = $tenant;
        $this->role = $role;
    }

    public function tenant(): Tenant
    {
        if ($this->tenant === null) {
            throw new \RuntimeException('No tenant context is set.');
        }

        return $this->tenant;
    }

    public function id(): int
    {
        return $this->tenant()->getKey();
    }

    public function role(): TenantRole
    {
        if ($this->role === null) {
            throw new \RuntimeException('No tenant role is set.');
        }

        return $this->role;
    }

    public function isSet(): bool
    {
        return $this->tenant !== null && $this->role !== null;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->role = null;
    }
}