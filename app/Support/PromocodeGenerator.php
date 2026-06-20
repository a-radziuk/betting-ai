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

    public static function generateUnique(int $days): Promocode
    {
        do {
            $code = self::prefix().Str::upper(Str::random(8));
        } while (Promocode::query()->where('code', $code)->exists());

        return Promocode::query()->create([
            'code' => $code,
            'days' => $days,
        ]);
    }
}
