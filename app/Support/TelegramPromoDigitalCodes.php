<?php

namespace App\Support;

final class TelegramPromoDigitalCodes
{
    /** @var list<string> */
    public const CODES = [
        '55501',
        '55502',
        '55503',
        '55504',
    ];

    public static function isStartCommand(string $text): bool
    {
        $text = trim($text);

        return $text === '/start' || str_starts_with($text, '/start@');
    }

    public static function matchInText(string $text): ?string
    {
        foreach (self::CODES as $code) {
            if (str_contains($text, $code)) {
                return $code;
            }
        }

        return null;
    }
}
