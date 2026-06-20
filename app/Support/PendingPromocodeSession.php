<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PendingPromocodeSession
{
    public const SESSION_KEY = 'pending_promocode';

    public static function store(string $code): void
    {
        session([self::SESSION_KEY => Str::upper(trim($code))]);
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
