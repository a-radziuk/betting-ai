<?php

namespace App\Support;

final class SubscriptionTerms
{
    public const SESSION_KEY = 'subscription_terms_acceptance';

    public static function version(): string
    {
        return (string) config('subscriptions.terms.version', '1');
    }

    public static function accept(string $planId): void
    {
        session([
            self::SESSION_KEY => [
                'plan_id' => $planId,
                'version' => self::version(),
                'accepted_at' => now()->timestamp,
            ],
        ]);
    }

    public static function acceptedForPlan(string $planId): bool
    {
        $data = session(self::SESSION_KEY);
        if (! is_array($data)) {
            return false;
        }

        return ($data['plan_id'] ?? '') === $planId
            && ($data['version'] ?? '') === self::version();
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
