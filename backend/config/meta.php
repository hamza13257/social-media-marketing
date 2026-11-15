<?php

return [

    'app_id' => env('META_APP_ID'),

    'app_secret' => env('META_APP_SECRET'),

    'redirect_uri' => env('META_REDIRECT_URI', 'http://127.0.0.1:8000/api/meta/oauth/callback'),

    'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),

    'frontend_callback' => env('FRONTEND_URL', 'http://localhost:3000').'/social-accounts',

    /*
    |--------------------------------------------------------------------------
    | OAuth scopes (official Meta Login)
    |--------------------------------------------------------------------------
    */
    'scopes' => array_filter(explode(',', env(
        'META_OAUTH_SCOPES',
        'public_profile,pages_show_list,pages_read_engagement,instagram_basic,business_management'
    ))),
];