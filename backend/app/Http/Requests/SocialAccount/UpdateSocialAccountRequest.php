<?php

namespace App\Http\Requests\SocialAccount;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSocialAccountRequest extends FormRequest
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
                'nullable',
                'integer',
                Rule::exists('brands', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
        ];
    }
}