<?php

namespace App\Console\Commands;

use App\Services\EventOddsExportPayload;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use JsonException;

class EventExportAllForUploadCommand extends Command
{
    protected $signature = 'event:export-all-for-upload';

    protected $description = 'Export unfinished upcoming events with markets, selections, and odds to storage/app/export_<Y-m-d>.json';

    public function handle(): int
    {
        if (! Schema::hasTable('events')) {
            $this->components->error('The events table does not exist.');

            return self::FAILURE;
        }

        $date = Carbon::now(config('app.timezone'))->format('Y-m-d');
        $path = storage_path('app/export_'.$date.'.json');

        $events = EventOddsExportPayload::unresolvedEventsQuery()->get();

        $payload = [
            'exportDate' => $date,
            'exportedAt' => now()->toIso8601String(),
            'events' => $events
                ->map(fn ($event) => EventOddsExportPayload::buildForUpload($event))
                ->values()
                ->all(),
        ];

        try {
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->components->error('Failed to encode export JSON: '.$e->getMessage());

            return self::FAILURE;
        }

        if (file_put_contents($path, $json) === false) {
            $this->components->error("Failed to write {$path}.");

            return self::FAILURE;
        }

        $eventCount = count($payload['events']);
        $this->components->info("Wrote {$eventCount} upcoming unfinished event(s) to {$path}");

        return self::SUCCESS;
    }
}
