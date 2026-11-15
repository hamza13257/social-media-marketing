<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tenant */
class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = null;
        if ($this->pivot?->role) {
            $role = $this->pivot->role;
        } elseif ($request->user()?->is_super_admin) {
            $role = 'owner';
        } elseif ($request->user()) {
            $role = $request->user()->roleOnTenant($this->resource)?->value;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'role' => $role,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}