<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Market;
use App\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Unfinished events whose kickoff is still in the future.
     *
     * @return Builder<Event>
     */
    public static function unresolvedEventsQuery(): Builder
    {
        return Event::query()
            ->with([
                'markets' => fn ($q) => $q->orderBy('id'),
                'markets.selections' => fn ($q) => $q->orderBy('id'),
                'markets.selections.odds' => fn ($q) => $q->orderBy('id'),
            ])
            ->where('status', '!=', Event::STATUS_FINISHED)
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildForUpload(Event $event): array
    {
        return array_merge(self::exportModelAttributes($event), [
            'markets' => $event->markets
                ->map(function (Market $market): array {
                    return array_merge(self::exportModelAttributes($market), [
                        'selections' => $market->selections
                            ->map(function ($selection): array {
                                return array_merge(self::exportModelAttributes($selection), [
                                    'odds' => $selection->odds
                                        ->map(fn ($odd) => self::exportModelAttributes($odd))
                                        ->values()
                                        ->all(),
                                ]);
                            })
                            ->values()
                            ->all(),
                    ]);
                })
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function exportModelAttributes(Model $model): array
    {
        $out = [];
        foreach (array_keys($model->getAttributes()) as $key) {
            $out[$key] = self::exportAttributeValue($model->getAttribute($key));
        }

        return $out;
    }

    private static function exportAttributeValue(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        return $value;
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
     *     homeTeam?: array{fifa_rank: int, fifa_points: float},
     *     awayTeam?: array{fifa_rank: int, fifa_points: float},
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
        $promrel = $tournament?->standings_promrel ?? [];

        $payload = [
            'eventId' => (string) $event->id,
            'eventName' => $eventName,
            'eventTournament' => $eventTournament,
            'eventDateTime' => $event->start_time?->toIso8601String(),
            'standings' => self::prepareStandings($tournament?->standings, $promrel),
            'odds' => array_values($rows),
        ];

        $homeFifa = self::teamFifaExportFields($home);
        if ($homeFifa !== null) {
            $payload['homeTeam'] = $homeFifa;
        }

        $awayFifa = self::teamFifaExportFields($away);
        if ($awayFifa !== null) {
            $payload['awayTeam'] = $awayFifa;
        }

        return $payload;
    }

    /**
     * @return array{fifa_rank?: int, fifa_points?: float}|null
     */
    private static function teamFifaExportFields(?Team $team): ?array
    {
        if ($team === null || $team->country !== 'World') {
            return null;
        }

        $fields = [];

        if ($team->fifa_rank !== null) {
            $fields['fifa_rank'] = (int) $team->fifa_rank;
        }

        if ($team->fifa_points !== null) {
            $fields['fifa_points'] = round((float) $team->fifa_points, 2);
        }

        return $fields === [] ? null : $fields;
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
        if ($standings === null) {
            return null;
        }

        if (isset($standings['groups']) && is_array($standings['groups'])) {
            $prepared = [];
            foreach ($standings['groups'] as $group) {
                if (! is_array($group)) {
                    continue;
                }

                $groupName = isset($group['name']) ? trim((string) $group['name']) : '';
                $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
                if ($rows === []) {
                    continue;
                }

                $totalGamesInGroup = max(count($rows) - 1, 0) * 1;
                foreach (self::prepareStandingsRows($rows, $standings_promrel, $totalGamesInGroup) as $row) {
                    if ($groupName !== '') {
                        $row['group'] = $groupName;
                    }
                    $prepared[] = $row;
                }
            }

            return $prepared === [] ? null : $prepared;
        }

        if (! isset($standings['rows']) || ! is_array($standings['rows'])) {
            return null;
        }

        $totalGamesInTheTournament = max(count($standings['rows']) - 1, 0) * 2;

        return self::prepareStandingsRows($standings['rows'], $standings_promrel, $totalGamesInTheTournament);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $standings_promrel
     * @return list<array<string, mixed>>
     */
    private static function prepareStandingsRows(array $rows, array $standings_promrel, int $totalGamesInTheTournament): array
    {
        return array_map(function ($row) use ($standings_promrel, $totalGamesInTheTournament) {
            if (! is_array($row)) {
                return $row;
            }
            $row['team'] = $row['team_display_name'] ?? $row['team'] ?? '';
            unset($row['team_path'], $row['form'], $row['movement'], $row['team_id'], $row['team_display_name']);

            $played = (int) ($row['played'] ?? 0);
            if ($played === 0) {
                $row['position'] = 0;
                unset($row['outcome'], $row['outcome_positivity']);
            } else {
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
            }

            $remainingGames = $totalGamesInTheTournament - $played;
            $potentialPointsToScore = $remainingGames * 3;

            $row['remaining_games'] = $remainingGames;
            $row['potential_points'] = $potentialPointsToScore;

            return $row;
        }, array_values($rows));
    }
}
