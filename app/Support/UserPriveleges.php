<?php

namespace App\Support;

use App\Models\User;

final class UserPriveleges
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            User::PRIVELEGE_SEE_TIPS => __('See tips'),
            User::PRIVELEGE_PLACE_BETS => __('Place bets'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    /**
     * @param  list<string>  $selected
     */
    public static function toStorage(array $selected): ?string
    {
        $valid = array_values(array_intersect($selected, self::keys()));

        return $valid === [] ? null : implode(',', $valid);
    }

    /**
     * @return list<string>
     */
    public static function fromStorage(?string $priveleges): array
    {
        if ($priveleges === null || trim($priveleges) === '') {
            return [];
        }

        return array_values(array_intersect(
            array_map(trim(...), explode(',', $priveleges)),
            self::keys(),
        ));
    }
}
