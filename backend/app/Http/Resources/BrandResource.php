<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Brand */
class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_path' => $this->logo_path,
            'website_url' => $this->website_url,
            'brand_colors' => $this->brand_colors,
            'target_audience' => $this->target_audience,
            'language' => $this->language,
            'tone_of_voice' => $this->tone_of_voice,
            'marketing_objectives' => $this->marketing_objectives,
            'products_services' => $this->products_services,
            'content_pillars' => $this->content_pillars,
            'brand_guidelines' => $this->brand_guidelines,
            'knowledge_documents' => $this->knowledge_documents,
            'archived_at' => $this->archived_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}