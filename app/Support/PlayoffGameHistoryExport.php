<?php

namespace App\Support;

final class PlayoffGameHistoryExport
{
    /**
     * @param  array<string, mixed>|null  $standings
     * @return list<array{
     *     team: string,
     *     games: list<array{
     *         result: string,
     *         summary: string,
     *         score: string,
     *         opponent: string,
     *         goals_scored: int,
     *         goals_conceded: int
     *     }>,
     *     goals_scored: int,
     *     goals_conceded: int
     * }>|null
     */
    public static function fromStandings(?array $standings): ?array
    {
        if ($standings === null) {
            return null;
        }

        $histories = [];

        if (isset($standings['groups']) && is_array($standings['groups'])) {
            foreach ($standings['groups'] as $group) {
                if (! is_array($group)) {
                    continue;
                }

                $groupName = isset($group['name']) ? trim((string) $group['name']) : '';
                $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];

                foreach ($rows as $row) {
                    $history = self::historyFromStandingsRow(is_array($row) ? $row : []);
                    if ($history === null) {
                        continue;
                    }

                    if ($groupName !== '') {
                        $history['group'] = $groupName;
                    }

                    $histories[] = $history;
                }
            }
        } elseif (isset($standings['rows']) && is_array($standings['rows'])) {
            foreach ($standings['rows'] as $row) {
                $history = self::historyFromStandingsRow(is_array($row) ? $row : []);
                if ($history !== null) {
                    $histories[] = $history;
                }
            }
        }

        return $histories === [] ? null : $histories;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *     team: string,
     *     games: list<array{
     *         result: string,
     *         summary: string,
     *         score: string,
     *         opponent: string,
     *         goals_scored: int,
     *         goals_conceded: int
     *     }>,
     *     goals_scored: int,
     *     goals_conceded: int,
     *     group?: string
     * }|null
     */
    private static function historyFromStandingsRow(array $row): ?array
    {
        $team = trim((string) ($row['team_display_name'] ?? $row['team'] ?? ''));
        if ($team === '') {
            return null;
        }

        $games = self::parseFormGames((string) ($row['form'] ?? ''));

        return [
            'team' => $team,
            'games' => $games,
            'goals_scored' => array_sum(array_column($games, 'goals_scored')),
            'goals_conceded' => array_sum(array_column($games, 'goals_conceded')),
        ];
    }

    /**
     * @return list<array{
     *     result: string,
     *     summary: string,
     *     score: string,
     *     opponent: string,
     *     goals_scored: int,
     *     goals_conceded: int
     * }>
     */
    public static function parseFormGames(?string $form): array
    {
        $games = [];

        foreach (GuardianFormIcons::parseSegments($form) as $segment) {
            $parsed = self::parseGameFromSummary($segment['tooltip']);
            if ($parsed !== null) {
                $games[] = $parsed;
            }
        }

        return $games;
    }

    /**
     * @return array{
     *     result: string,
     *     summary: string,
     *     score: string,
     *     opponent: string,
     *     goals_scored: int,
     *     goals_conceded: int
     * }|null
     */
    private static function parseGameFromSummary(string $summary): ?array
    {
        if (! preg_match('/^(Won|Lost|Drew)\s+(\d+)\s*[-:–]\s*(\d+)\s+(against|to|with)\s+(.+)$/u', trim($summary), $matches)) {
            return null;
        }

        $goalsScored = (int) $matches[2];
        $goalsConceded = (int) $matches[3];
        $result = match ($matches[1]) {
            'Won' => 'win',
            'Lost' => 'loss',
            default => 'draw',
        };

        return [
            'result' => $result,
            'summary' => trim($summary),
            'score' => $goalsScored.'-'.$goalsConceded,
            'opponent' => trim($matches[5]),
            'goals_scored' => $goalsScored,
            'goals_conceded' => $goalsConceded,
        ];
    }
}
