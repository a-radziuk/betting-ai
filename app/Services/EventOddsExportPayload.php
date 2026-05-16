<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Market;
use Illuminate\Database\Eloquent\Builder;

final class EventOddsExportPayload
{
    /**
     * @return Builder<Event>
     */
    public static function queryWithOddsTree(): Builder
    {
        return Event::query()->with([
            'tournament',
            'homeTeam.tournament',
            'awayTeam.tournament',
            'markets' => fn ($q) => $q->orderBy('id'),
            'markets.selections' => fn ($q) => $q->orderBy('id'),
            'markets.selections.odds' => fn ($q) => $q->orderBy('id'),
        ]);
    }

    public static function findForExport(int|string $eventId, bool $withOdds = true): ?Event
    {
        if ($withOdds) {
            return self::queryWithOddsTree()->find($eventId);
        }

        return Event::query()->with([
            'tournament',
            'homeTeam.tournament',
            'awayTeam.tournament',
        ])->find($eventId);
    }

    /**
     * @param  list<string>  $excludeMarketTypes  Market `type` values to skip (case-insensitive in input; compared to stored type).
     * @return array{
     *     eventId: string,
     *     eventName: string,
     *     eventTournament: string|null,
     *     eventDateTime: string|null,
     *     standings: array<string, mixed>|null,
     *     odds: list<array<string, mixed>>
     * }
     */
    public static function build(Event $event, array $excludeMarketTypes = [], bool $includeOdds = true): array
    {
        $rows = $includeOdds ? self::buildOddsRows($event, $excludeMarketTypes) : [];

        $home = $event->homeTeam;
        $away = $event->awayTeam;
        $eventName = ($home && $away) ? "{$home->resolvedDisplayName()} vs {$away->resolvedDisplayName()}" : '';
        $eventTournament = $event->tournament?->name;

        $tournament = $event->tournament;
        $promrel = is_array($tournament?->standings_promrel) ? $tournament->standings_promrel : [];

        return [
            'eventId' => (string) $event->id,
            'eventName' => $eventName,
            'eventTournament' => $eventTournament,
            'eventDateTime' => $event->start_time?->toIso8601String(),
            'standings' => self::prepareStandings($tournament?->standings, $promrel),
            'odds' => array_values($rows),
        ];
    }

    /**
     * @param  list<string>  $excludeMarketTypes
     * @return list<array<string, mixed>>
     */
    private static function buildOddsRows(Event $event, array $excludeMarketTypes): array
    {
        $excluded = array_flip($excludeMarketTypes);

        $rows = [];
        foreach ($event->markets as $market) {
            if (isset($excluded[$market->type])) {
                continue;
            }
            foreach ($market->selections as $selection) {
                foreach ($selection->odds as $odd) {
                    $rows[] = [
                        'id' => $odd->id !== null ? $odd->id : null,
                        'type' => $market->type,
                        'period' => $market->period,
                        'selection' => $selection->name,
                        'odds' => $odd->odds !== null ? (float) $odd->odds : null,
                        'handicap_home_team' => $market->type === Market::TYPE_HANDICAP ? ($selection->handicap !== null ? (float) $selection->handicap : null) : null,
                    ];
                }
            }
        }

        $rows = array_map(function ($row) {
            if ($row['handicap_home_team'] === null) {
                unset($row['handicap_home_team']);
            }
            if ($row['type'] === Market::TYPE_HANDICAP) {
                $row['selection'] = substr($row['selection'], 0, 4);
            }

            return $row;
        }, $rows);

        return array_values(array_filter($rows, fn ($row) => $row['id'] !== null));
    }

    /**
     * @param  array<string, mixed>|null  $standings
     * @param  array<string, mixed>  $standings_promrel
     * @return list<array<string, mixed>>|null
     */
    private static function prepareStandings(?array $standings, array $standings_promrel): ?array
    {
        if ($standings === null || ! isset($standings['rows']) || ! is_array($standings['rows'])) {
            return null;
        }

        $totalGamesInTheTournament = (count($standings['rows']) - 1) * 2;

        return array_map(function ($row) use ($standings_promrel, $totalGamesInTheTournament) {
            if (! is_array($row)) {
                return $row;
            }
            $row['team'] = $row['team_display_name'] ?? $row['team'] ?? '';
            unset($row['team_path'], $row['form'], $row['movement'], $row['team_id'], $row['team_display_name']);
            $posKey = isset($row['position']) ? (string) $row['position'] : '';
            if ($posKey !== '' && isset($standings_promrel[$posKey])) {
                $pr = $standings_promrel[$posKey];
                $effect = 'Relegation to ';
                if (($pr['type'] ?? '') === 'promotion') {
                    $effect = 'Promotion to ';
                }
                $effect .= $pr['name'] ?? '';
                $row['outcome'] = $effect;
                $row['outcome_positivity'] = $pr['positivity'] ?? 'Unknown';
            } else {
                $row['outcome'] = 'None';
                $row['outcome_positivity'] = 0;
            }

            $remainingGames = $totalGamesInTheTournament - (int) ($row['played'] ?? 0);
            $potentialPointsToScore = $remainingGames * 3;

            $row['remaining_games'] = $remainingGames;
            $row['potential_points'] = $potentialPointsToScore;

            return $row;
        }, array_values($standings['rows']));
    }
}
