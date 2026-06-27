<?php

namespace App\Support;

final class TelegramPartnerCodes
{
    public static function isStartCommand(string $text): bool
    {
        $text = trim($text);

        return $text === '/start' || str_starts_with($text, '/start@');
    }

    public static function isFiveDigitCode(string $text): bool
    {
        return preg_match('/^\d{5}$/', trim($text)) === 1;
    }

    public static function matchPartnerCode(string $text): ?string
    {
        $text = trim($text);

        if (! self::isFiveDigitCode($text)) {
            return null;
        }

        return in_array($text, self::codes(), true) ? $text : null;
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        $codes = config('telegram_promobot.partner_codes', ['48201', '55501', '84920']);

        return array_values(array_map(
            static fn (mixed $code): string => (string) $code,
            is_array($codes) ? $codes : [],
        ));
    }

    public static function referralLink(string $partnerCode): string
    {
        $prefix = (string) config('referrals.code_prefix', 'REF-');

        return route('referral.promocode', [
            'promocode' => $prefix.$partnerCode,
        ], absolute: true);
    }
}
