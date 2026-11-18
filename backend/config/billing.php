<?php

use App\Enums\PaymentGatewayDriver;

return [

    /*
    |--------------------------------------------------------------------------
    | Active payment gateway (you choose the driver)
    |--------------------------------------------------------------------------
    */
    'gateway' => PaymentGatewayDriver::tryFrom((string) env('PAYMENT_GATEWAY', 'manual'))
        ?? PaymentGatewayDriver::Manual,

    'manual' => [
        'instructions' => env(
            'MANUAL_PAYMENT_INSTRUCTIONS',
            'Contact billing to complete payment. Your workspace activates after confirmation.'
        ),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    | Plan limits (JSON on subscription_plans.limits). Null = unlimited.
    | posts_per_day, reels_per_week, stories_per_day — scheduled/published slots
    | ai_generations_month — Groq caption/post generations
    | brands, social_accounts, team_members, storage_mb
    */
    'default_plan_slug' => env('DEFAULT_PLAN_SLUG', 'starter'),

    'enforce_active_subscription' => env('BILLING_ENFORCE_ACTIVE', true),
];