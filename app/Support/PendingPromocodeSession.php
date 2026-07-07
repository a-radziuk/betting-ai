<?php

namespace App\Support;

use App\Models\Promocode;
use Illuminate\Support\Str;

final class PendingPromocodeSession
{
    public const SESSION_KEY = 'pending_promocode';

    public static function store(string $code): void
    {
        session([self::SESSION_KEY => Str::upper(trim($code))]);
    }

    public static function peek(): ?string
    {
        $code = session(self::SESSION_KEY);

        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        return Str::upper(trim($code));
    }

    public static function activePromocode(): ?Promocode
    {
        $code = self::peek();
        if ($code === null) {
            return null;
        }

        $promocode = Promocode::query()->where('code', $code)->first();
        if ($promocode === null || $promocode->isUsed()) {
            return null;
        }

        $user = auth()->user();
        if ($user !== null && $promocode->hasBeenUsedByUser($user)) {
            return null;
        }

        return $promocode;
    }

    public static function pull(): ?string
    {
        $code = session()->pull(self::SESSION_KEY);

        if (! is_string($code) || trim($code) === '') {
            return null;
        }

        return Str::upper(trim($code));
    }
}
