<?php

namespace App\Support;

final class StripeConfig
{
    public static function isConfigured(): bool
    {
        $key = config('stripe.key');
        $secret = config('stripe.secret');

        return is_string($key) && $key !== ''
            && is_string($secret) && $secret !== '';
    }
}
