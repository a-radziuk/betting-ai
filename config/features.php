<?php

/**
 * Feature flags (boolean). Each entry reads from .env using FEATURE_<SCREAMING_SNAKE>.
 *
 * Add a line here and a matching env key, then use:
 * - PHP: feature('your_flag')
 * - Blade: @feature('your_flag') … @endfeature
 *
 * Unknown flag names resolve to false.
 */
return [
    'example_widget' => filter_var(env('FEATURE_EXAMPLE_WIDGET', false), FILTER_VALIDATE_BOOLEAN),
    'player_stats_csv_download' => filter_var(env('FEATURE_PLAYER_STATS_CSV_DOWNLOAD', false), FILTER_VALIDATE_BOOLEAN),
    'subscription_stripe_payments' => filter_var(env('FEATURE_SUBSCRIPTION_STRIPE_PAYMENTS', false), FILTER_VALIDATE_BOOLEAN),
    'simple_crypto_payment' => filter_var(env('FEATURE_SIMPLE_CRYPTO_PAYMENT', false), FILTER_VALIDATE_BOOLEAN),
    'pay_with_metamask' => filter_var(env('FEATURE_PAY_WITH_METAMASK', false), FILTER_VALIDATE_BOOLEAN),
    'login_google' => filter_var(env('FEATURE_LOGIN_GOOGLE', false), FILTER_VALIDATE_BOOLEAN),
    'login_facebook' => filter_var(env('FEATURE_LOGIN_FACEBOOK', false), FILTER_VALIDATE_BOOLEAN),
    'login_github' => filter_var(env('FEATURE_LOGIN_GITHUB', false), FILTER_VALIDATE_BOOLEAN),
];
