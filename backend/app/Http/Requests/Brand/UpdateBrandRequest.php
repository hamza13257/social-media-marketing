<?php

namespace App\Http\Requests\Brand;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'brand_colors' => ['nullable', 'array'],
            'brand_colors.*' => ['string', 'max:32'],
            'target_audience' => ['nullable', 'string', 'max:2000'],
            'language' => ['nullable', 'string', 'max:16'],
            'tone_of_voice' => ['nullable', 'string', 'max:255'],
            'marketing_objectives' => ['nullable', 'array'],
            'products_services' => ['nullable', 'array'],
            'content_pillars' => ['nullable', 'array'],
            'brand_guidelines' => ['nullable', 'string'],
            'archived' => ['sometimes', 'boolean'],
        ];
    }
}