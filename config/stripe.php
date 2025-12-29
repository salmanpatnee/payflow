<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | The Stripe publishable key and secret key give you access to Stripe's
    | API. The "publishable" key is typically used client-side and the
    | "secret" key accesses private account data and should not be shared.
    |
    */

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The Stripe webhook secret is used to verify that webhook requests are
    | actually coming from Stripe. This value should be kept secret and
    | should match the value set in your Stripe dashboard.
    |
    */

    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe API Version
    |--------------------------------------------------------------------------
    |
    | This value determines which version of Stripe's API will be used.
    |
    */

    'api_version' => env('STRIPE_API_VERSION', '2023-10-16'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | This value determines the default currency used for payments.
    |
    */

    'currency' => env('STRIPE_CURRENCY', 'usd'),

];
