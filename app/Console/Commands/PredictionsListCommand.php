<?php

namespace App\Console\Commands;

use App\Models\EventPrediction;
use App\Services\EventPredictionExportPayload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PredictionsListCommand extends Command
{
    protected $signature = 'predictions:list';

    protected $description = 'List all active event predictions';

    public function handle(): int
    {
        if (! Schema::hasTable('event_predictions')) {
            $this->components->error('The event_predictions table does not exist.');

            return self::FAILURE;
        }

        $predictions = EventPredictionExportPayload::activePredictionsQuery()
            ->with(['event.homeTeam', 'event.awayTeam'])
            ->get();

        if ($predictions->isEmpty()) {
            $this->components->info('No active event predictions found.');

            return self::SUCCESS;
        }

        $rows = $predictions->map(function (EventPrediction $prediction): array {
            $event = $prediction->event;

            return [
                $prediction->id,
                $event?->id ?? '—',
                $this->eventLabel($prediction),
                $event?->status ?? '—',
                $event?->start_time?->format('Y-m-d H:i') ?? '—',
                (string) $prediction->prediction_type,
                $prediction->odds_id,
                $prediction->bank_percentage,
                $prediction->confidence ?? '—',
                Str::limit((string) $prediction->explanation, 60),
            ];
        })->all();

        $this->table(
            ['ID', 'Event ID', 'Event', 'Status', 'Start', 'Type', 'Odds ID', 'Bank %', 'Conf', 'Explanation'],
            $rows,
        );

        $this->components->info("Showing {$predictions->count()} active prediction(s).");

        return self::SUCCESS;
    }

    private function eventLabel(EventPrediction $prediction): string
    {
        $event = $prediction->event;
        if ($event === null) {
            return '—';
        }

        $home = $event->homeTeam?->short_name ?? $event->homeTeam?->name ?? '?';
        $away = $event->awayTeam?->short_name ?? $event->awayTeam?->name ?? '?';

        return "{$home} vs {$away}";
    }
}
