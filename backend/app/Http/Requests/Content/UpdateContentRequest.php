<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic' => ['sometimes', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:100'],
            'call_to_action' => ['nullable', 'string', 'max:500'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}