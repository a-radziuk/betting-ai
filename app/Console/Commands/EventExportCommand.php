<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Market;
use Illuminate\Console\Command;

class EventExportCommand extends Command
{
    protected $signature = 'event:export
        {eventId : Event primary key}';

    protected $description = 'Export event metadata and all odds as JSON to stdout';

    public function handle(): int
    {
        $eventId = $this->argument('eventId');

        $event = Event::query()
            ->with([
                'homeTeam.tournament',
                'awayTeam.tournament',
                'markets' => fn ($q) => $q->orderBy('id'),
                'markets.selections' => fn ($q) => $q->orderBy('id'),
                'markets.selections.odds' => fn ($q) => $q->orderBy('id'),
            ])
            ->find($eventId);

        if ($event === null) {
            $this->components->error('Event not found.');

            return self::FAILURE;
        }

        $rows = [];
        foreach ($event->markets as $market) {
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
        $eventName = ($home && $away) ? "{$home->name} vs {$away->name}" : '';
        $eventTournament = $home?->tournament?->name
            ?? $away?->tournament?->name
            ?? $home?->league
            ?? $away?->league;

        $payload = [
            'eventId' => $eventId,
            'eventName' => $eventName,
            'eventTournament' => $eventTournament,
            'eventDateTime' => $event->start_time?->toIso8601String(),
            'odds' => array_values($rows),
        ];

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
