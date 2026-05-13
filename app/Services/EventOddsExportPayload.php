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

    public static function findForExport(int|string $eventId): ?Event
    {
        return self::queryWithOddsTree()->find($eventId);
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
    public static function build(Event $event, array $excludeMarketTypes = []): array
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

        $rows = array_filter($rows, function ($row) {
            return $row['id'] !== null;
        });

        $home = $event->homeTeam;
        $away = $event->awayTeam;
        $eventName = ($home && $away) ? "{$home->resolvedDisplayName()} vs {$away->resolvedDisplayName()}" : '';
        $eventTournament = $event->tournament?->name;

        return [
            'eventId' => (string) $event->id,
            'eventName' => $eventName,
            'eventTournament' => $eventTournament,
            'eventDateTime' => $event->start_time?->toIso8601String(),
            'standings' => self::prepareStandings($event->tournament?->standings, $event->tournament->standings_promrel),
            'odds' => array_values($rows),
        ];
    }

    protected static function prepareStandings( array $standings , array $standings_promrel): array
    {
        return array_map(function ($row) use ($standings_promrel) {
            $row['team'] = $row['team_display_name'];
            unset($row['team_path'], $row['form'], $row['movement'], $row['team_id'], $row['team_display_name']);
            if (isset($standings_promrel[$row['position']])) {
                $pr = $standings_promrel[$row['position']];
                $effect = 'Relegation to ';
                if ($pr['type'] === 'promotion') {
                    $effect = 'Promotion to ';
                }
                $effect .= $pr['name'];
                $row['outcome'] = $effect;
            }
            return $row;
        }, array_values($standings['rows']));
    }
}
