<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

final class GuardianStandingsParser
{
    /**
     * Parse Guardian football league table HTML (e.g. Premier League).
     *
     * @return array{rows: list<array<string, mixed>>}
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
        $table = $this->findStandingsTable($xpath);
        if ($table === null) {
            throw new RuntimeException('No Guardian-style standings table found in HTML.');
        }

        $rows = [];
        foreach ($xpath->query('./tbody/tr', $table) as $tr) {
            if (! $tr instanceof DOMElement) {
                continue;
            }
            $parsed = $this->parseDataRow($xpath, $tr);
            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }

        if ($rows === []) {
            throw new RuntimeException('Standings table has no data rows.');
        }

        return ['rows' => $rows];
    }

    private function findStandingsTable(DOMXPath $xpath): ?DOMElement
    {
        foreach ($xpath->query('//table[thead and tbody]') as $table) {
            if (! $table instanceof DOMElement) {
                continue;
            }
            $hasTeam = $xpath->query('./thead//th[contains(normalize-space(.), "Team")]', $table)->length > 0;
            $hasGp = $xpath->query('./thead//abbr[@title="Games played"]', $table)->length > 0
                || $xpath->query('./thead//th[contains(normalize-space(.), "GP")]', $table)->length > 0;
            if ($hasTeam && $hasGp) {
                return $table;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseDataRow(DOMXPath $xpath, DOMElement $tr): ?array
    {
        $tds = $xpath->query('./td', $tr);
        $teamTh = $xpath->query('./th[@scope="row"]', $tr)->item(0);
        if ($tds->length < 10 || ! $teamTh instanceof DOMElement) {
            return null;
        }

        $link = $teamTh->getElementsByTagName('a')->item(0);
        $team = $link instanceof DOMElement ? trim($link->textContent) : trim($teamTh->textContent);
        $teamPath = $link instanceof DOMElement ? ($link->getAttribute('href') ?: null) : null;

        $position = $this->intOrNull($tds->item(0)?->textContent ?? '');
        $played = $this->intOrNull($tds->item(1)?->textContent ?? '');
        $won = $this->intOrNull($tds->item(2)?->textContent ?? '');
        $drawn = $this->intOrNull($tds->item(3)?->textContent ?? '');
        $lost = $this->intOrNull($tds->item(4)?->textContent ?? '');
        $goalsFor = $this->intOrNull($tds->item(5)?->textContent ?? '');
        $goalsAgainst = $this->intOrNull($tds->item(6)?->textContent ?? '');
        $goalDifference = $this->intOrNull($tds->item(7)?->textContent ?? '');
        $points = $this->intOrNull($tds->item(8)?->textContent ?? '');
        $form = trim(preg_replace('/\s+/u', ' ', $tds->item(9)?->textContent ?? '') ?? '');

        if ($position === null || $team === '') {
            return null;
        }

        return [
            'position' => $position,
            'team' => $team,
            'team_path' => $teamPath,
            'played' => $played,
            'won' => $won,
            'drawn' => $drawn,
            'lost' => $lost,
            'goals_for' => $goalsFor,
            'goals_against' => $goalsAgainst,
            'goal_difference' => $goalDifference,
            'points' => $points,
            'form' => $form !== '' ? $form : null,
        ];
    }

    private function intOrNull(string $raw): ?int
    {
        $t = trim($raw);
        if ($t === '' || $t === '—' || $t === '-') {
            return null;
        }
        if (! preg_match('/^-?\d+$/', $t)) {
            return null;
        }

        return (int) $t;
    }
}
