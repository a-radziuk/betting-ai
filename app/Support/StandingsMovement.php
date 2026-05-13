<?php

namespace App\Support;

final class StandingsMovement
{
    /**
     * Add a `movement` field to each standings row: `up`, `down`, or `none`, by comparing
     * each team's league position to the previous snapshot (when available).
     *
     * @param  array{rows?: list<array<string, mixed>>}  $newStandings
     * @param  array{rows?: list<array<string, mixed>>}|null  $previousStandings  Full prior `tournament.standings` value; null or empty rows → all `none`.
     * @return array{rows: list<array<string, mixed>>}
     */
    public static function apply(array $newStandings, ?array $previousStandings): array
    {
        $rows = $newStandings['rows'] ?? [];
        if (! is_array($rows)) {
            return $newStandings;
        }

        $oldPositionByTeam = self::positionIndexFromRows(
            is_array($previousStandings) ? ($previousStandings['rows'] ?? []) : []
        );

        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($oldPositionByTeam === []) {
                $rows[$i]['movement'] = 'none';

                continue;
            }

            $team = isset($row['team']) ? trim((string) $row['team']) : '';
            $newPos = self::normalizePosition($row['position'] ?? null);

            if ($team === '' || $newPos === null || ! array_key_exists($team, $oldPositionByTeam)) {
                $rows[$i]['movement'] = 'none';

                continue;
            }

            $oldPos = $oldPositionByTeam[$team];
            if ($newPos < $oldPos) {
                $rows[$i]['movement'] = 'up';
            } elseif ($newPos > $oldPos) {
                $rows[$i]['movement'] = 'down';
            } else {
                $rows[$i]['movement'] = 'none';
            }
        }

        $newStandings['rows'] = $rows;

        return $newStandings;
    }

    /**
     * @param  list<array<string, mixed>>|array<int, mixed>  $rows
     * @return array<string, int>
     */
    private static function positionIndexFromRows(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $team = isset($row['team']) ? trim((string) $row['team']) : '';
            if ($team === '') {
                continue;
            }
            $pos = self::normalizePosition($row['position'] ?? null);
            if ($pos === null) {
                continue;
            }
            $map[$team] = $pos;
        }

        return $map;
    }

    private static function normalizePosition(mixed $position): ?int
    {
        if ($position === null) {
            return null;
        }
        if (is_int($position)) {
            return $position;
        }
        if (is_string($position) && preg_match('/^-?\d+$/', trim($position))) {
            return (int) trim($position);
        }

        return null;
    }
}
