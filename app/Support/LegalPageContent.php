<?php

namespace App\Support;

final class LegalPageContent
{
    /**
     * @return array<string, string>
     */
    public static function parameters(): array
    {
        return [
            '[DATE]' => (string) config('legal.date', ''),
            '[WEBSITE NAME]' => (string) config('app.name', ''),
            '[CONTACT EMAIL]' => (string) config('legal.contact_email', ''),
            '[WEBSITE URL]' => (string) config('app.url', ''),
            '[COUNTRY/STATE]' => (string) config('legal.country', ''),
        ];
    }

    public static function render(string $content): string
    {
        return str_replace(
            array_keys(self::parameters()),
            array_values(self::parameters()),
            $content,
        );
    }
}
