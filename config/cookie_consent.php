<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Consent Version
    |--------------------------------------------------------------------------
    |
    | Increment when categories or policy change to invalidate prior consent.
    |
    */

    'version' => env('COOKIE_CONSENT_VERSION', '1'),

    'cookie_name' => env('COOKIE_CONSENT_COOKIE_NAME', 'cookie_consent'),

    'cookie_lifetime_days' => (int) env('COOKIE_CONSENT_LIFETIME_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Cookie Categories
    |--------------------------------------------------------------------------
    */

    'categories' => [
        'essential' => [
            'label' => 'Essential',
            'description' => 'Required for the site to work, including security, session, and consent storage.',
            'required' => true,
        ],
        'analytics' => [
            'label' => 'Analytics',
            'description' => 'Helps us understand how visitors use the site so we can improve it.',
            'required' => false,
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Used to deliver relevant offers and measure campaign performance.',
            'required' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Non-Essential Scripts
    |--------------------------------------------------------------------------
    |
    | Loaded in the browser only after the matching category is consented to.
    |
    */

    'scripts' => [
        'analytics' => array_values(array_filter([
            env('GOOGLE_ANALYTICS_ID') ? [
                'type' => 'external',
                'src' => 'https://www.googletagmanager.com/gtag/js?id='.env('GOOGLE_ANALYTICS_ID'),
                'async' => true,
            ] : null,
            env('GOOGLE_ANALYTICS_ID') ? [
                'type' => 'inline',
                'content' => "window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','".env('GOOGLE_ANALYTICS_ID')."');",
            ] : null,
        ])),
        'marketing' => [],
    ],

];
