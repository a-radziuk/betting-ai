<?php

return [

    'terms' => [
        'slug' => 'subscription-terms',
        'version' => env('SUBSCRIPTION_TERMS_VERSION', '1'),
    ],

    'currency' => env('SUBSCRIPTION_CURRENCY', 'EUR'),

    'plans' => [
        'one_day' => [
            'name' => '1 day',
            'duration_label' => '1 day',
            'visible' => filter_var(env('SUBSCRIPTION_ONE_DAY_VISIBLE', true), FILTER_VALIDATE_BOOLEAN),
            'price' => env('SUBSCRIPTION_ONE_DAY_PRICE', '2.99'),
        ],
        'one_week' => [
            'name' => '1 week',
            'duration_label' => '1 week',
            'visible' => filter_var(env('SUBSCRIPTION_ONE_WEEK_VISIBLE', true), FILTER_VALIDATE_BOOLEAN),
            'price' => env('SUBSCRIPTION_ONE_WEEK_PRICE', '9.99'),
        ],
        'one_month' => [
            'name' => '1 month',
            'duration_label' => '1 month',
            'visible' => filter_var(env('SUBSCRIPTION_ONE_MONTH_VISIBLE', true), FILTER_VALIDATE_BOOLEAN),
            'price' => env('SUBSCRIPTION_ONE_MONTH_PRICE', '29.99'),
        ],
        'three_months' => [
            'name' => '3 months',
            'duration_label' => '3 months',
            'visible' => filter_var(env('SUBSCRIPTION_THREE_MONTHS_VISIBLE', true), FILTER_VALIDATE_BOOLEAN),
            'price' => env('SUBSCRIPTION_THREE_MONTHS_PRICE', '79.99'),
        ],
        'one_year' => [
            'name' => '1 year',
            'duration_label' => '1 year',
            'visible' => filter_var(env('SUBSCRIPTION_ONE_YEAR_VISIBLE', true), FILTER_VALIDATE_BOOLEAN),
            'price' => env('SUBSCRIPTION_ONE_YEAR_PRICE', '249.99'),
        ],
    ],

];
