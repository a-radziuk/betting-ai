<?php

namespace App\Support;

final class StandingsPromrelDecoder
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return self::normalize($value);
        }

        if (! is_string($value)) {
            return [];
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode(self::sanitizeJsonText($trimmed), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        if (is_string($decoded)) {
            $decoded = json_decode(self::sanitizeJsonText($decoded), true);
        }

        return is_array($decoded) ? self::normalize($decoded) : [];
    }

    private static function sanitizeJsonText(string $value): string
    {
        return str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}", '“', '”', '‘', '’'],
            ['"', '"', "'", "'", '"', '"', "'", "'"],
            $value
        );
    }

    /**
     * @param  array<mixed, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private static function normalize(array $data): array
    {
        $normalized = [];
        foreach ($data as $position => $zone) {
            if (! is_array($zone)) {
                continue;
            }

            $normalized[(string) $position] = $zone;
        }

        return $normalized;
    }
}
