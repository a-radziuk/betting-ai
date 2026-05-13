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
        {tournamentId? : Optional tournament primary key; omit for all tournaments}
        {--no-markets= : Comma-separated market types to omit from each event export (passed to event:export)}
        {--full : Also write <date>.txt with the same JSON plus bet-finder instructions}';

    protected $description = 'Run event:export for each event scheduled today (app timezone) that has not started yet; write JSON (events grouped by tournament) to storage/app/<Y-m-d>.json; with --full, also write <Y-m-d>.txt (same JSON plus prompt)';

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

        $hasTournamentId = Schema::hasColumn('events', 'tournament_id');
        $columns = $hasTournamentId ? ['id', 'start_time', 'tournament_id'] : ['id', 'start_time'];

        $events = $query->orderBy('start_time')->orderBy('id')->get($columns);

        if ($events->isEmpty()) {
            $this->components->warn('No matching events for today.');
        }

        $grouped = $hasTournamentId
            ? $events->groupBy('tournament_id')
            : $events->groupBy(fn () => 0);

        $sortedGroups = $grouped->sortBy(fn ($group) => $group->min('start_time'));

        $tournamentById = [];
        if ($hasTournamentId && Schema::hasTable('tournaments')) {
            $ids = $sortedGroups->keys()
                ->filter(fn ($key) => $key !== null && $key !== '')
                ->map(fn ($key) => (int) $key)
                ->unique()
                ->values()
                ->all();
            if ($ids !== []) {
                $tournamentById = Tournament::query()->whereIn('id', $ids)->get()->keyBy('id')->all();
            }
        }

        $payloads = [];

        foreach ($sortedGroups as $rawTournamentKey => $eventGroup) {
            $tournamentId = null;
            if ($hasTournamentId && $rawTournamentKey !== null && $rawTournamentKey !== '') {
                $tournamentId = (int) $rawTournamentKey;
            }

            $tournamentName = 'Unknown';
            if ($tournamentId !== null && isset($tournamentById[$tournamentId])) {
                $tournamentName = (string) $tournamentById[$tournamentId]->name;
            }

            $eventPayloads = [];

            foreach ($eventGroup as $event) {
                $code = Artisan::call('event:export', $this->eventExportArguments($event->id));
                if ($code !== self::SUCCESS) {
                    $this->components->error("event:export failed for event {$event->id}.");

                    return self::FAILURE;
                }

                try {
                    $eventPayloads[] = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    $this->components->error("Invalid JSON from event:export for event {$event->id}: {$e->getMessage()}");

                    return self::FAILURE;
                }
            }

            $payloads[] = [
                'tournamentId' => $tournamentId,
                'tournamentName' => $tournamentName,
                'events' => $eventPayloads,
            ];
        }

        $path = storage_path('app/'.$today.'.json');

        $eventCount = $events->count();

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

        if ($this->option('full')) {
            $txtPath = storage_path('app/'.$today.'.txt');
            $instruction = $this->fullExportInstructionTextDaily($eventCount);
            $txtBody = $json."\n\n".$instruction;
            if (file_put_contents($txtPath, $txtBody) === false) {
                $this->components->error('Could not write to '.$txtPath);

                return self::FAILURE;
            }
            $this->components->info('Also wrote '.$txtPath);
        }

        $groupCount = count($payloads);
        $this->components->info("Wrote {$groupCount} tournament group(s) ({$eventCount} event export(s)) to {$path}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function eventExportArguments(int $eventId): array
    {
        $params = ['eventId' => $eventId];
        $raw = $this->option('no-markets');
        if (is_string($raw) && trim($raw) !== '') {
            $params['--no-markets'] = $raw;
        }

        return $params;
    }

    private function fullExportInstructionTextDaily(int $numberOfEvents): string
    {
        $type = 'DAILY';
        return <<<TXT
Above is the odds for {$numberOfEvents} games that are happening today. Out of these games find:
1/ the safest bet
2/ the best bet
3/ the potential upset bet
4/ the never win bet
Do not consider odds that are too low even for the safest strategy.
Give me those bets as JSON in the following format:
{
    odd_id: // id from the JSON
    stake: // percent from 1000
    description: // explain why you want to bet
    type: // possible values - GPT_MANUAL_{$type}_SAFEST, GPT_MANUAL_{$type}_BEST, GPT_MANUAL_{$type}_UPSET, GPT_MANUAL_{$type}_NEVERWIN
}
TXT;
    }
}
