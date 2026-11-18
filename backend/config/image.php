<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image generation (Phase 3)
    |--------------------------------------------------------------------------
    |
    | provider: none | openai_compatible | stability (wire when you add keys)
    */
    'provider' => env('IMAGE_PROVIDER', 'none'),

    'openai_compatible' => [
        'api_key' => env('IMAGE_API_KEY', env('AI_API_KEY')),
        'base_url' => env('IMAGE_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('IMAGE_MODEL', 'dall-e-3'),
    ],
];