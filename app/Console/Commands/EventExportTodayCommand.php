<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use JsonException;

class EventExportTodayCommand extends Command
{
    protected $signature = 'event:export-today
        {tournamentId? : Optional tournament primary key; omit for all tournaments}';

    protected $description = 'Run event:export for each event scheduled today (app timezone) that has not started yet; write JSON array to storage/app/<Y-m-d>.json';

    public function handle(): int
    {
        if (! Schema::hasTable('events')) {
            $this->components->error('The events table does not exist.');

            return self::FAILURE;
        }

        $tz = config('app.timezone');
        $today = Carbon::now($tz)->format('Y-m-d');
        $tournamentIdArg = $this->argument('tournamentId');

        if ($tournamentIdArg !== null && $tournamentIdArg !== '') {
            $tid = (int) $tournamentIdArg;
            if (! Schema::hasTable('tournaments') || ! Tournament::query()->whereKey($tid)->exists()) {
                $this->components->error("Tournament [{$tid}] not found.");

                return self::FAILURE;
            }
        }

        $query = Event::query()
            ->where('start_time', '>', now())
            ->whereDate('start_time', $today);

        if ($tournamentIdArg !== null && $tournamentIdArg !== '' && Schema::hasColumn('events', 'tournament_id')) {
            $query->where('tournament_id', (int) $tournamentIdArg);
        }

        $events = $query->orderBy('start_time')->orderBy('id')->get(['id']);

        if ($events->isEmpty()) {
            $this->components->warn('No matching events for today.');
        }

        $payloads = [];

        foreach ($events as $event) {
            $code = Artisan::call('event:export', ['eventId' => $event->id]);
            if ($code !== self::SUCCESS) {
                $this->components->error("event:export failed for event {$event->id}.");

                return self::FAILURE;
            }

            try {
                $payloads[] = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $this->components->error("Invalid JSON from event:export for event {$event->id}: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        $path = storage_path('app/'.$today.'.json');

        try {
            $json = json_encode($payloads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->components->error("Failed to encode output file: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (file_put_contents($path, $json) === false) {
            $this->components->error('Could not write to '.$path);

            return self::FAILURE;
        }

        $this->components->info('Wrote '.count($payloads).' export(s) to '.$path);

        return self::SUCCESS;
    }
}
