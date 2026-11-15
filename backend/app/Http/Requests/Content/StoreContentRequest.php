<?php

namespace App\Http\Requests\Content;

use App\Enums\ContentFormat;
use App\Enums\ContentPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->attributes->get('tenant')?->id;

        return [
            'brand_id' => [
                'required',
                'integer',
                Rule::exists('brands', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'topic' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', Rule::enum(ContentPlatform::class)],
            'format' => ['required', Rule::enum(ContentFormat::class)],
            'language' => ['nullable', 'string', 'max:16'],
            'caption' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:100'],
            'call_to_action' => ['nullable', 'string', 'max:500'],
        ];
    }
}