<?php

namespace App\Support;

use App\Models\Promocode;
use Illuminate\Support\Str;

final class PromocodeGenerator
{
    public static function prefix(): string
    {
        return (string) config('promocodes.prefix', 'PROMO-');
    }

    public static function generateUnique(int $days, ?string $prefix = null, bool $isMultiple = false): Promocode
    {
        $prefix ??= self::prefix();

        do {
            $code = $prefix.Str::upper(Str::random(8));
        } while (Promocode::query()->where('code', $code)->exists());

        return Promocode::query()->create([
            'code' => $code,
            'days' => $days,
            'is_multiple' => $isMultiple,
        ]);
    }

    public static function generateUniqueMulti(int $days, ?string $prefix = null): Promocode
    {
        return self::generateUnique($days, $prefix, true);
    }
}
