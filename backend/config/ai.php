<?php

return [

    'provider' => env('AI_PROVIDER', 'openai_compatible'),

    'api_key' => env('AI_API_KEY'),

    // Groq (2026): prefer openai/gpt-oss-20b — llama-3.x IDs may return model_not_found on developer tier
    'base_url' => env('AI_BASE_URL', 'https://api.groq.com/openai/v1'),

    'model' => env('AI_MODEL', 'openai/gpt-oss-20b'),

    'timeout' => (int) env('AI_TIMEOUT', 60),

    'json_response' => filter_var(env('AI_JSON_RESPONSE', true), FILTER_VALIDATE_BOOLEAN),
];