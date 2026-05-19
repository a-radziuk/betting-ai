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
        {--no-odds : Omit odds from each event export (passed to event:export)}
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

            foreach ($eventGroup as $scheduledEvent) {
                $code = Artisan::call('event:export', $this->eventExportArguments($scheduledEvent->id));
                if ($code !== self::SUCCESS) {
                    $this->components->error("event:export failed for event {$scheduledEvent->id}.");

                    return self::FAILURE;
                }

                try {
                    $eventPayloads[] = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    $this->components->error("Invalid JSON from event:export for event {$scheduledEvent->id}: {$e->getMessage()}");

                    return self::FAILURE;
                }
            }

            $standings = null;
            foreach ($eventPayloads as &$exportedEvent) {
                $standings = $exportedEvent['standings'];
                unset($exportedEvent['standings']);
            }
            unset($exportedEvent);

            $payloads[] = [
                'tournamentId' => $tournamentId,
                'tournamentName' => $tournamentName,
                'standings' => $standings,
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
        if ($this->option('no-odds')) {
            $params['--no-odds'] = true;
        }

        return $params;
    }

    private function fullExportInstructionTextDaily(int $numberOfEvents): string
    {
        if ($this->option('no-odds')) {
            return $this->fullExportInstructionTextDailyWithoutOdds($numberOfEvents);
        }

        $type = 'DAILY';

        return <<<TXT
Above is the odds for {$numberOfEvents} games that are happening today. Out of these games find:
1/ the safest bet
For the safest strategy, do not consider odds below 1.20

2/ the best bet
The best bet is typically the one between 1.5 and 3

3/ the potential upset bet
The upsetter goes for odds that are typically between 3 and 5, but in some cases can be higher

If the event JSON includes a "standings" array, you must use it before choosing a bet. Each row describes one club in the competition table: league position, team name, matches played and results (won/drawn/lost), goals for and against, goal difference, total points, a textual "outcome" describing what that position currently means (e.g. title race, European qualification, mid-table, relegation), an "outcome_positivity" score reflecting how desirable or urgent that situation is (higher for title or European chase, lower or negative for relegation danger), plus "remaining_games" and "potential_points" showing how much can still change in the table.
Match each side in the fixture (from event name and context) to its standings row. Infer motivation and likely approach: teams fighting for the title or a European spot often press for wins; those in a relegation battle may be desperate or tight; clubs with little left to play for may rotate, conserve energy, or lack intensity; late-season gaps between points and potential_points reveal whether a result is must-win, enough for a draw, or largely irrelevant.
Weave this motivation analysis into your probability estimates and into the explanation when standings are present. If "standings" is missing or empty, state that limitation briefly and rely on odds and match context only.
Give me those bets as JSON in the following format:
{
    odd_id: // id from the JSON
    stake: // percent from 1000
    description: // explain why you want to bet
    type: // possible values - GPT_MANUAL_{$type}_SAFEST, GPT_MANUAL_{$type}_BEST, GPT_MANUAL_{$type}_UPSET
    confidence: // how confident you are that this bet will win, int from 1 to 10
}
TXT;
    }

    private function fullExportInstructionTextDailyWithoutOdds(int $numberOfEvents): string
    {
        $gamesLabel = $numberOfEvents === 1 ? 'game' : 'games';

        return <<<TXT
Above are {$numberOfEvents} {$gamesLabel} happening today (fixture list and tournament context only — no betting odds are included).

For each game, state the most likely outcome (home win, draw, or away win), how many goals will be scored approximately based primarily on the tournament standings. Analyse the standings thoroughly: match each club in the fixture to its table row and infer motivation (title race, European qualification, relegation fight, mid-table comfort, dead rubbers, etc.) using position, points, goal difference, remaining games, potential points, and the outcome / outcome_positivity fields where present.
When analysing motivation of each team, also consider other teams who have plus-minus the same amount of points and a similar outcome based on their position in the table. . If there is some other game or games provided in the list of events that can influence this game in any way, please mention them in the description and in the "influenced_by" field
Explain your reasoning per fixture in terms of what each team is playing for and how that shapes the probable result. If standings are missing or empty for a competition, say so briefly and use only the fixture names and any other context in the JSON.

Respond as JSON: an array of objects, one per event, each with eventId (from the JSON), eventName(from the JSON),  likely_outcome (HOME_WIN | DRAW | AWAY_WIN), approximate_goals,  description (your motivation-based explanation), home_motivation (motivation of the home team ranging 0 to 10), away_motivation (motivation of the away team ranging 0 to 10), home_class (class of the home team ranging 0 to 10), away_class (class of the away team ranging 0 to 10), influenced_by (another game or games that can potentially influence the outcome of this game, null if there's no such games),  influenced_by_event_ids (event_ids of the games from the JSON that potentially influence the outcome of this game, null if there's none).
TXT;
    }
}
