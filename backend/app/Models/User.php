<?php

namespace App\Models;

use App\Enums\TenantRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar_url', 'is_super_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function roleOnTenant(Tenant $tenant): ?TenantRole
    {
        if ($this->is_super_admin) {
            return TenantRole::Owner;
        }

        $pivot = $this->tenants()
            ->where('tenants.id', $tenant->getKey())
            ->first()?->pivot;

        if ($pivot === null) {
            return null;
        }

        return TenantRole::tryFrom((string) $pivot->role);
    }

    public function belongsToTenant(Tenant $tenant): bool
    {
        return $this->is_super_admin || $this->tenants()->where('tenants.id', $tenant->getKey())->exists();
    }
}