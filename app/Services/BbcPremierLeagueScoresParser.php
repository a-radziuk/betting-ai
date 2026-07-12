<?php

namespace App\Services;

use InvalidArgumentException;

class BbcPremierLeagueScoresParser
{
    /**
     * Parsed BBC Sport scores-fixtures HTML.
     *
     * @return list<array{homeName: string, awayName: string, homeGoals: int, awayGoals: int, status: string}>
     */
    public function parseFinishedResults(string $html): array
    {
        $fromMatchDivs = $this->parseFinishedResultsFromMatchDivs($html);
        if ($fromMatchDivs !== [] || $this->hasMatchDivBlocks($html)) {
            return $fromMatchDivs;
        }

        return $this->parseFinishedResultsFromEmbeddedJson($html);
    }

    /**
     * @return list<array{homeName: string, awayName: string, homeGoals: int, awayGoals: int, status: string}>
     */
    private function parseFinishedResultsFromMatchDivs(string $html): array
    {
        $out = [];

        foreach ($this->extractMatchDivBlocks($html) as $block) {
            if (! str_contains($block, '>FT<')) {
                continue;
            }

            $names = $this->extractDesktopTeamNames($block);
            if (count($names) < 2) {
                continue;
            }

            $scores = $this->extractScores($block);
            if ($scores === null) {
                continue;
            }

            $out[] = [
                'homeName' => html_entity_decode($names[0], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'awayName' => html_entity_decode($names[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'homeGoals' => $scores[0],
                'awayGoals' => $scores[1],
                'status' => 'FT',
            ];
        }

        return $out;
    }

    private function hasMatchDivBlocks(string $html): bool
    {
        return $this->extractMatchDivBlocks($html) !== [];
    }

    /**
     * @return list<string>
     */
    private function extractMatchDivBlocks(string $html): array
    {
        if (! preg_match_all(
            '/<div data-event-id="[^"]+" class="ssrcss-1bjtunb-GridContainer[^"]*">/s',
            $html,
            $matches,
            PREG_OFFSET_CAPTURE,
        )) {
            return [];
        }

        $blocks = [];
        $count = count($matches[0]);
        $htmlLength = strlen($html);

        for ($i = 0; $i < $count; $i++) {
            $start = $matches[0][$i][1];
            $end = ($i + 1 < $count) ? $matches[0][$i + 1][1] : $htmlLength;
            $blocks[] = substr($html, $start, $end - $start);
        }

        return $blocks;
    }

    /**
     * @return list<string>
     */
    private function extractDesktopTeamNames(string $block): array
    {
        if (! preg_match_all(
            '/<span aria-hidden="true" class="ssrcss-1p14tic-DesktopValue[^"]*">([^<]*)<\/span>/s',
            $block,
            $matches,
        )) {
            return [];
        }

        return array_values(array_map('trim', $matches[1]));
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function extractScores(string $block): ?array
    {
        if (! preg_match(
            '/<div class="ssrcss-qsbptj-HomeScore[^"]*">(\d+)<\/div>.*?<div class="ssrcss-fri5a2-AwayScore[^"]*">(\d+)<\/div>/s',
            $block,
            $matches,
        )) {
            return null;
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    /**
     * @return list<array{homeName: string, awayName: string, homeGoals: int, awayGoals: int, status: string}>
     */
    private function parseFinishedResultsFromEmbeddedJson(string $html): array
    {
        $unescaped = str_replace('\\"', '"', $html);
        $needle = '"eventGroups":';
        $pos = strpos($unescaped, $needle);
        if ($pos === false) {
            throw new InvalidArgumentException('Could not locate eventGroups JSON in BBC page HTML.');
        }

        $jsonStart = $pos + strlen($needle);
        $len = strlen($unescaped);
        while ($jsonStart < $len && ctype_space($unescaped[$jsonStart])) {
            $jsonStart++;
        }
        if (($unescaped[$jsonStart] ?? '') !== '[') {
            throw new InvalidArgumentException('eventGroups payload is not a JSON array.');
        }

        $arrayJson = $this->extractBalancedBracket($unescaped, $jsonStart, '[', ']');
        if ($arrayJson === null) {
            throw new InvalidArgumentException('Could not parse eventGroups array.');
        }

        /** @var list<mixed> $groups */
        $groups = json_decode($arrayJson, true, 512, JSON_THROW_ON_ERROR);
        $events = $this->flattenEvents($groups);

        $out = [];
        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }
            $status = $event['status'] ?? null;
            if ($status !== 'PostEvent') {
                continue;
            }

            $home = $event['home'] ?? null;
            $away = $event['away'] ?? null;
            if (! is_array($home) || ! is_array($away)) {
                continue;
            }

            $homeName = isset($home['fullName']) ? (string) $home['fullName'] : '';
            $awayName = isset($away['fullName']) ? (string) $away['fullName'] : '';
            if ($homeName === '' || $awayName === '') {
                continue;
            }

            $hs = $home['score'] ?? null;
            $as = $away['score'] ?? null;
            if (! is_numeric($hs) || ! is_numeric($as)) {
                continue;
            }

            $out[] = [
                'homeName' => $homeName,
                'awayName' => $awayName,
                'homeGoals' => (int) $hs,
                'awayGoals' => (int) $as,
                'status' => (string) $status,
            ];
        }

        return $out;
    }

    /**
     * @param  list<mixed>  $groups
     * @return list<mixed>
     */
    private function flattenEvents(array $groups): array
    {
        $events = [];
        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }
            if (isset($group['events']) && is_array($group['events'])) {
                foreach ($group['events'] as $ev) {
                    $events[] = $ev;
                }
            }
            if (isset($group['secondaryGroups']) && is_array($group['secondaryGroups'])) {
                foreach ($this->flattenEvents($group['secondaryGroups']) as $ev) {
                    $events[] = $ev;
                }
            }
        }

        return $events;
    }

    private function extractBalancedBracket(string $s, int $start, string $open, string $close): ?string
    {
        $depth = 0;
        $len = strlen($s);
        $inString = false;
        $escape = false;

        for ($i = $start; $i < $len; $i++) {
            $c = $s[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($c === '\\') {
                    $escape = true;
                } elseif ($c === '"') {
                    $inString = false;
                }
            } elseif ($c === '"') {
                $inString = true;
            } elseif ($c === $open) {
                $depth++;
            } elseif ($c === $close) {
                $depth--;
                if ($depth === 0) {
                    return substr($s, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }
}
