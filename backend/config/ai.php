<?php

return [

    'provider' => env('AI_PROVIDER', 'openai_compatible'),

    'api_key' => env('AI_API_KEY'),

    // Groq: AI_BASE_URL=https://api.groq.com/openai/v1  AI_MODEL=llama-3.3-70b-versatile
    'base_url' => env('AI_BASE_URL', 'https://api.groq.com/openai/v1'),

    'model' => env('AI_MODEL', 'llama-3.3-70b-versatile'),

    'timeout' => (int) env('AI_TIMEOUT', 60),
];