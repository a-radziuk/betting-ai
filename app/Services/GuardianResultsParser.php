<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

/**
 * Parses The Guardian football results pages (e.g. Premier League /results).
 * Relies on current DOM class hooks (dcr-*) used for full-time rows.
 */
final class GuardianResultsParser
{
    /**
     * @return list<array{homeName: string, awayName: string, homeGoals: int, awayGoals: int}>
     */
    public function parseHtml(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="UTF-8">'.$html;
        if (! @$dom->loadHTML($wrapped, LIBXML_NOWARNING | LIBXML_NOERROR)) {
            libxml_clear_errors();

            throw new RuntimeException('Could not parse HTML document.');
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $anchors = $xpath->query('//a[.//span[normalize-space()="FT"]]');
        $out = [];

        foreach ($anchors as $a) {
            if (! $a instanceof DOMElement) {
                continue;
            }

            $parsed = $this->parseMatchAnchor($xpath, $a);
            if ($parsed !== null) {
                $out[] = $parsed;
            }
        }

        return $out;
    }

    /**
     * @return array{homeName: string, awayName: string, homeGoals: int, awayGoals: int}|null
     */
    private function parseMatchAnchor(DOMXPath $xpath, DOMElement $anchor): ?array
    {
        $homeSpan = $xpath->query('.//span[contains(@class, "dcr-iqim6o")]', $anchor)->item(0);
        $homeName = $homeSpan !== null ? trim($homeSpan->textContent) : '';

        $homeGoalsEl = $xpath->query('.//span[contains(@class, "dcr-79z44d")]', $anchor)->item(0);
        $awayGoalsEl = $xpath->query('.//span[contains(@class, "dcr-1c2czlv")]', $anchor)->item(0);

        $awayDiv = $xpath->query('.//div[contains(@class, "dcr-rm7qtf")]', $anchor)->item(0);
        $awayName = '';
        if ($awayDiv instanceof DOMElement) {
            $awayName = trim(preg_replace('/\s+/u', ' ', $awayDiv->textContent) ?? '');
        }

        if ($homeName === '' || $awayName === '' || $homeGoalsEl === null || $awayGoalsEl === null) {
            return null;
        }

        $hg = trim($homeGoalsEl->textContent);
        $ag = trim($awayGoalsEl->textContent);
        if (! preg_match('/^\d+$/', $hg) || ! preg_match('/^\d+$/', $ag)) {
            return null;
        }

        return [
            'homeName' => $homeName,
            'awayName' => $awayName,
            'homeGoals' => (int) $hg,
            'awayGoals' => (int) $ag,
        ];
    }
}
