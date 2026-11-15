<?php

use App\Enums\PaymentGatewayDriver;

return [

    /*
    |--------------------------------------------------------------------------
    | Active payment gateway (you choose the driver)
    |--------------------------------------------------------------------------
    */
    'gateway' => PaymentGatewayDriver::from(env('PAYMENT_GATEWAY', 'manual')),

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
    | Null limit = unlimited for that metric
    */
    'default_plan_slug' => env('DEFAULT_PLAN_SLUG', 'starter'),

    'enforce_active_subscription' => env('BILLING_ENFORCE_ACTIVE', true),
];