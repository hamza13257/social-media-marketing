<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\WeekPlan */
class WeekPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'brand_id' => $this->brand_id,
            'week_start' => $this->week_start?->toDateString(),
            'brief' => $this->brief,
            'rhythm' => $this->rhythm,
            'status' => $this->status,
            'generated_summary' => $this->generated_summary,
            'error_message' => $this->error_message,
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
            ]),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
            'contents_count' => $this->when(isset($this->contents_count), $this->contents_count),
            'contents' => ContentResource::collection($this->whenLoaded('contents')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}