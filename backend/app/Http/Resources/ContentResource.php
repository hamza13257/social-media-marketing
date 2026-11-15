<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Content */
class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'brand_id' => $this->brand_id,
            'created_by_user_id' => $this->created_by_user_id,
            'topic' => $this->topic,
            'objective' => $this->objective,
            'platform' => $this->platform->value,
            'format' => $this->format->value,
            'language' => $this->language,
            'caption' => $this->caption,
            'hashtags' => $this->hashtags,
            'call_to_action' => $this->call_to_action,
            'media_assets' => $this->media_assets,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'status' => $this->status->value,
            'social_account_id' => $this->social_account_id,
            'external_post_id' => $this->external_post_id,
            'published_at' => $this->published_at?->toIso8601String(),
            'version' => $this->version,
            'internal_notes' => $this->internal_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
            ]),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ]),
        ];
    }
}