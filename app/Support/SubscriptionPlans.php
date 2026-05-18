<?php

namespace App\Support;

final class SubscriptionPlans
{
    public const FREE_TRIAL = 'free_trial';

    public const THREE_MONTHS = 'three_months';

    public const ONE_YEAR = 'one_year';

    /**
     * @return list<array{id: string, name: string, duration_label: string, enabled: bool}>
     */
    public static function all(): array
    {
        return [
            [
                'id' => self::FREE_TRIAL,
                'name' => 'Free trial',
                'duration_label' => '1 month',
                'enabled' => true,
            ],
            [
                'id' => self::THREE_MONTHS,
                'name' => '3 months',
                'duration_label' => '3 months',
                'enabled' => false,
            ],
            [
                'id' => self::ONE_YEAR,
                'name' => '1 year',
                'duration_label' => '1 year',
                'enabled' => false,
            ],
        ];
    }

    /**
     * @return array{id: string, name: string, duration_label: string, enabled: bool}|null
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
        $plan = self::find($id);

        return $plan !== null && $plan['enabled'];
    }
}
