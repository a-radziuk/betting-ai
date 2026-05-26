<?php

namespace App\Support;

final class GuardianFormIcons
{
    /**
     * Split Guardian-style form text into segments (e.g. "Won …Lost …Drew …").
     *
     * @return list<array{letter: 'W'|'L'|'D', tooltip: string}>
     */
    public static function parseSegments(?string $form): array
    {
        $form = trim((string) $form);
        if ($form === '') {
            return [];
        }

        if (! preg_match_all('/(Won|Lost|Drew)(.*?)(?=(?:Won|Lost|Drew)|$)/s', $form, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $out = [];
        foreach ($matches as $row) {
            $prefix = $row[1];
            $rest = $row[2] ?? '';
            $tooltip = trim(__($prefix).$rest);
            $letter = match ($prefix) {
                'Won' => 'W',
                'Lost' => 'L',
                'Drew' => 'D',
            };
            $out[] = ['letter' => $letter, 'tooltip' => $tooltip];
        }

        return $out;
    }
}
