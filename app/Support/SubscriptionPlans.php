<?php

namespace App\Support;

final class SubscriptionPlans
{
    public const ONE_WEEK = 'one_week';

    public const ONE_MONTH = 'one_month';

    public const THREE_MONTHS = 'three_months';

    public const ONE_YEAR = 'one_year';

    /**
     * Visible plans only; each returned plan is enabled (subscribable).
     *
     * @return list<array{
     *     id: string,
     *     name: string,
     *     duration_label: string,
     *     price: string,
     *     price_label: string,
     *     enabled: bool
     * }>
     */
    public static function all(): array
    {
        $currency = (string) config('subscriptions.currency', 'EUR');
        $plans = [];

        foreach (config('subscriptions.plans', []) as $id => $plan) {
            if (! ($plan['visible'] ?? false)) {
                continue;
            }

            $price = (string) ($plan['price'] ?? '0');

            $plans[] = [
                'id' => (string) $id,
                'name' => (string) $plan['name'],
                'duration_label' => (string) $plan['duration_label'],
                'price' => $price,
                'price_label' => self::formatPrice($price, $currency),
                'enabled' => true,
            ];
        }

        return $plans;
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     duration_label: string,
     *     price: string,
     *     price_label: string,
     *     enabled: bool
     * }|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $plan) {
            if ($plan['id'] === $id) {
                return $plan;
            }
        }

        return null;
    }

    public static function isEnabled(string $id): bool
    {
        return self::find($id) !== null;
    }

    public static function formatPrice(string $price, string $currency): string
    {
        $amount = number_format((float) $price, 2);

        return match (strtoupper($currency)) {
            'EUR' => '€'.$amount,
            'USD' => '$'.$amount,
            'GBP' => '£'.$amount,
            default => $amount.' '.$currency,
        };
    }
}
