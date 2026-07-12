<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

final class BbcStandingsParser
{
    /**
     * Parse BBC Sport football league table HTML.
     *
     * @return array{rows: list<array<string, mixed>>}
     */
    public function parseHtml(string $html): array
    {
        $dom = $this->loadDom($html);
        $xpath = new DOMXPath($dom);
        $table = $this->findStandingsTable($xpath);

        if ($table === null) {
            throw new RuntimeException('No BBC standings table found in HTML.');
        }

        $rows = $this->parseTableRows($xpath, $table);
        if ($rows === []) {
            throw new RuntimeException('BBC standings table has no data rows.');
        }

        return ['rows' => $rows];
    }

    private function loadDom(string $html): DOMDocument
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="UTF-8">'.$html;
        if (! @$dom->loadHTML($wrapped, LIBXML_NOWARNING | LIBXML_NOERROR)) {
            libxml_clear_errors();

            throw new RuntimeException('Could not parse HTML document.');
        }
        libxml_clear_errors();

        return $dom;
    }

    private function findStandingsTable(DOMXPath $xpath): ?DOMElement
    {
        $table = $xpath->query("//table[@data-testid='football-table']")->item(0);
        if ($table instanceof DOMElement) {
            return $table;
        }

        foreach ($xpath->query('//table[thead and tbody]') as $candidate) {
            if (! $candidate instanceof DOMElement) {
                continue;
            }

            if ($xpath->query('.//span[contains(@class, "VisuallyHidden") and normalize-space(.)="Team"]', $candidate)->length > 0) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseTableRows(DOMXPath $xpath, DOMElement $table): array
    {
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

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseDataRow(DOMXPath $xpath, DOMElement $tr): ?array
    {
        $teamCell = $xpath->query('./td[@aria-label="Team"]', $tr)->item(0);
        if (! $teamCell instanceof DOMElement) {
            return null;
        }

        $position = $this->intOrNull($this->textFromXPath($xpath, './/span[contains(@class, "Rank")]', $teamCell));
        $team = $this->teamNameFromCell($xpath, $teamCell);
        if ($position === null || $team === '') {
            return null;
        }

        $formCell = $xpath->query('./td[starts-with(@aria-label, "Form")]', $tr)->item(0);
        $form = '';
        if ($formCell instanceof DOMElement) {
            foreach ($xpath->query('.//div[@data-testid="letter-content"]', $formCell) as $letter) {
                if ($letter instanceof DOMElement) {
                    $form .= trim($letter->textContent);
                }
            }
        }

        return [
            'position' => $position,
            'team' => $team,
            'team_path' => null,
            'played' => $this->intOrNull($this->cellText($xpath, $tr, 'Played')),
            'won' => $this->intOrNull($this->cellText($xpath, $tr, 'Won')),
            'drawn' => $this->intOrNull($this->cellText($xpath, $tr, 'Drawn')),
            'lost' => $this->intOrNull($this->cellText($xpath, $tr, 'Lost')),
            'goals_for' => $this->intOrNull($this->cellText($xpath, $tr, 'Goals For')),
            'goals_against' => $this->intOrNull($this->cellText($xpath, $tr, 'Goals Against')),
            'goal_difference' => $this->intOrNull($this->cellText($xpath, $tr, 'Goal Difference')),
            'points' => $this->intOrNull($this->cellText($xpath, $tr, 'Points')),
            'form' => $form !== '' ? $form : null,
        ];
    }

    private function teamNameFromCell(DOMXPath $xpath, DOMElement $teamCell): string
    {
        $dataName = $xpath->query('.//span[@data-600]', $teamCell)->item(0);
        if ($dataName instanceof DOMElement) {
            $fromAttribute = trim($dataName->getAttribute('data-600'));
            if ($fromAttribute !== '') {
                return $fromAttribute;
            }
        }

        $hidden = $xpath->query('.//span[contains(@class, "VisuallyHidden")]', $teamCell)->item(0);
        if ($hidden instanceof DOMElement) {
            return trim(preg_replace('/\s+/u', ' ', $hidden->textContent) ?? '');
        }

        return trim(preg_replace('/\s+/u', ' ', $teamCell->textContent) ?? '');
    }

    private function cellText(DOMXPath $xpath, DOMElement $tr, string $ariaLabel): string
    {
        $cell = $xpath->query('./td[@aria-label="'.$ariaLabel.'"]', $tr)->item(0);
        if (! $cell instanceof DOMElement) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', $cell->textContent) ?? '');
    }

    private function textFromXPath(DOMXPath $xpath, string $query, DOMElement $context): string
    {
        $node = $xpath->query($query, $context)->item(0);
        if (! $node instanceof DOMElement) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
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
