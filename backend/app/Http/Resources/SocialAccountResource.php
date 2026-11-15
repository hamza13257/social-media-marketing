<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SocialAccount */
class SocialAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'brand_id' => $this->brand_id,
            'platform' => $this->platform->value,
            'platform_label' => $this->platform->label(),
            'external_id' => $this->external_id,
            'name' => $this->name,
            'username' => $this->username,
            'profile_picture_url' => $this->profile_picture_url,
            'connection_status' => $this->connection_status->value,
            'token_expires_at' => $this->token_expires_at?->toIso8601String(),
            'scopes' => $this->scopes,
            'metadata' => $this->metadata,
            'last_error' => $this->last_error,
            'connected_at' => $this->connected_at?->toIso8601String(),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
            ]),
        ];
    }
}