<?php

namespace App\Support;

use Carbon\CarbonInterface;
use InvalidArgumentException;

final class SubscriptionPlans
{
    public const ONE_DAY = 'one_day';

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

    public static function currency(): string
    {
        return strtoupper((string) config('subscriptions.currency', 'EUR'));
    }

    public static function amountInMinorUnits(string $planId): int
    {
        $plan = config('subscriptions.plans.'.$planId);
        if (! is_array($plan)) {
            throw new InvalidArgumentException("Unknown subscription plan [{$planId}].");
        }

        return (int) round((float) ($plan['price'] ?? 0) * 100);
    }

    public static function accessExpiresAtFrom(CarbonInterface $from, string $planId): CarbonInterface
    {
        return match ($planId) {
            self::ONE_DAY => $from->copy()->addDay(),
            self::ONE_WEEK => $from->copy()->addWeek(),
            self::ONE_MONTH => $from->copy()->addMonth(),
            self::THREE_MONTHS => $from->copy()->addMonths(3),
            self::ONE_YEAR => $from->copy()->addYear(),
            default => throw new InvalidArgumentException("Unknown subscription plan [{$planId}]."),
        };
    }
}
