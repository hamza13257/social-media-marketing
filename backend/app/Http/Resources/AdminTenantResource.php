<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Tenant */
class AdminTenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at?->toIso8601String(),
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'users_count' => $this->whenCounted('users'),
            'brands_count' => $this->whenCounted('brands'),
        ];
    }
}