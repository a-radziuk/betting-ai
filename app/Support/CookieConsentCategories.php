<?php

namespace App\Support;

final class CookieConsentCategories
{
    /**
     * @return array<string, array{label: string, description: string, required: bool}>
     */
    public static function all(): array
    {
        /** @var array<string, array{label: string, description: string, required: bool}> $categories */
        $categories = config('cookie_consent.categories', []);

        return $categories;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return list<string>
     */
    public static function optionalKeys(): array
    {
        return array_values(array_filter(
            self::keys(),
            fn (string $key): bool => ! (self::all()[$key]['required'] ?? false),
        ));
    }

    /**
     * @param  array<string, bool>  $choices
     * @return array<string, bool>
     */
    public static function normalize(array $choices, bool $acceptAll): array
    {
        $normalized = [];

        foreach (self::all() as $key => $meta) {
            if ($meta['required'] ?? false) {
                $normalized[$key] = true;

                continue;
            }

            $normalized[$key] = $acceptAll ? true : (bool) ($choices[$key] ?? false);
        }

        return $normalized;
    }

    /**
     * @param  array<string, bool>  $categories
     */
    public static function hasOptionalConsent(array $categories): bool
    {
        foreach (self::optionalKeys() as $key) {
            if ($categories[$key] ?? false) {
                return true;
            }
        }

        return false;
    }
}
