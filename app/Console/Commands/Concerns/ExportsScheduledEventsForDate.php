<?php

namespace App\Console\Commands\Concerns;

use App\Models\Event;
use App\Models\Tournament;
use App\Support\PlayoffGameHistoryExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use JsonException;

trait ExportsScheduledEventsForDate
{
    abstract protected function exportDate(Carbon $now): string;

    abstract protected function exportDayWord(): string;

    public function handleScheduledEventsExport(): int
    {
        if (! Schema::hasTable('events')) {
            $this->components->error('The events table does not exist.');

            return self::FAILURE;
        }

        $tz = config('app.timezone');
        $date = $this->exportDate(Carbon::now($tz));
        $dayWord = $this->exportDayWord();
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
            ->whereDate('start_time', $date);

        if ($tournamentIdArg !== null && $tournamentIdArg !== '' && Schema::hasColumn('events', 'tournament_id')) {
            $query->where('tournament_id', (int) $tournamentIdArg);
        }

        $hasTournamentId = Schema::hasColumn('events', 'tournament_id');
        $columns = $hasTournamentId ? ['id', 'start_time', 'tournament_id'] : ['id', 'start_time'];

        $events = $query->orderBy('start_time')->orderBy('id')->get($columns);

        if ($events->isEmpty()) {
            $this->components->warn("No matching events for {$dayWord}.");
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
        $playoffTournamentNames = [];
        $hasRegularTournaments = false;

        foreach ($sortedGroups as $rawTournamentKey => $eventGroup) {
            $tournamentId = null;
            if ($hasTournamentId && $rawTournamentKey !== null && $rawTournamentKey !== '') {
                $tournamentId = (int) $rawTournamentKey;
            }

            $tournamentName = 'Unknown';
            $isPlayoff = false;
            if ($tournamentId !== null && isset($tournamentById[$tournamentId])) {
                $tournament = $tournamentById[$tournamentId];
                $tournamentName = (string) $tournament->name;
                $isPlayoff = (bool) $tournament->is_playoff;
            }

            if ($isPlayoff) {
                $playoffTournamentNames[] = $tournamentName;
            } else {
                $hasRegularTournaments = true;
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
            $gameHistory = null;

            if ($isPlayoff && $tournamentId !== null && isset($tournamentById[$tournamentId])) {
                $gameHistory = PlayoffGameHistoryExport::fromStandings($tournamentById[$tournamentId]->standings);
            } else {
                foreach ($eventPayloads as &$exportedEvent) {
                    $standings = $exportedEvent['standings'];
                    unset($exportedEvent['standings']);
                }
                unset($exportedEvent);
            }

            $payloads[] = [
                'tournamentId' => $tournamentId,
                'tournamentName' => $tournamentName,
                'isPlayoff' => $isPlayoff,
                'standings' => $standings,
                'game_history' => $gameHistory,
                'events' => $eventPayloads,
            ];
        }

        $path = storage_path('app/'.$date.'.json');

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
            $txtPath = storage_path('app/'.$date.'.txt');
            $instruction = $this->fullExportInstructionTextDaily(
                $eventCount,
                $dayWord,
                $playoffTournamentNames,
                $hasRegularTournaments,
            );
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

    /**
     * @param  list<string>  $playoffTournamentNames
     */
    private function fullExportInstructionTextDaily(
        int $numberOfEvents,
        string $dayWord,
        array $playoffTournamentNames = [],
        bool $hasRegularTournaments = true,
    ): string {
        $hasPlayoffTournaments = $playoffTournamentNames !== [];

        if ($this->option('no-odds')) {
            return $this->fullExportInstructionTextDailyWithoutOdds(
                $numberOfEvents,
                $dayWord,
                $playoffTournamentNames,
                $hasRegularTournaments,
            );
        }

        $type = 'DAILY';

        return <<<TXT
Above is the odds for {$numberOfEvents} games that are happening {$dayWord}. Out of these games find:
1/ the safest bet
For the safest strategy, do not consider odds below 1.20
The stake must be 30

2/ the best bet
The best bet is typically the one between 1.5 and 3
The stake must be 20

3/ the potential upset bet
The upsetter goes for odds that are typically between 3 and 5, but in some cases can be higher. But don't go for long-shot upsets and unrealistic results
The stake must be 10

4/ the best bet for odds between 1.5 & 1.9
The most realistic bet exactly between these odds
The stake must be 50

{$this->handicapInstructionParagraph()}{$this->fifaRankingsInstructionParagraph($hasRegularTournaments, $hasPlayoffTournaments)}{$this->playoffTournamentsInstructionParagraph($playoffTournamentNames)}{$this->contextAnalysisInstructionParagraphWithOdds($hasRegularTournaments, $hasPlayoffTournaments)}Give me those bets as JSON in the following format:
{
    odd_id: // id from the JSON
    odd_value: // value of the odd
    market: // name of the market
    selection: // name of the selection
    stake: // percent from 1000
    description: // explain why you want to bet
    type: // possible values - GPT_MANUAL_{$type}_SAFEST, GPT_MANUAL_{$type}_BEST, GPT_MANUAL_{$type}_UPSET, GPT_MANUAL_{$type}_2x1
    confidence: // how confident you are that this bet will win, int from 1 to 10
}
TXT;
    }

    /**
     * @param  list<string>  $playoffTournamentNames
     */
    private function fullExportInstructionTextDailyWithoutOdds(
        int $numberOfEvents,
        string $dayWord,
        array $playoffTournamentNames = [],
        bool $hasRegularTournaments = true,
    ): string {
        $hasPlayoffTournaments = $playoffTournamentNames !== [];
        $gamesLabel = $numberOfEvents === 1 ? 'game' : 'games';

        return <<<TXT
Above are {$numberOfEvents} {$gamesLabel} happening {$dayWord} (fixture list and tournament context only — no betting odds are included).

{$this->fifaRankingsInstructionParagraph($hasRegularTournaments, $hasPlayoffTournaments)}{$this->playoffTournamentsInstructionParagraph($playoffTournamentNames)}{$this->contextAnalysisInstructionParagraphWithoutOdds($hasRegularTournaments, $hasPlayoffTournaments)}Respond as JSON: an array of objects, one per event, each with eventId (from the JSON), eventName(from the JSON),  likely_outcome (HOME_WIN | DRAW | AWAY_WIN), approximate_goals,  description (your motivation-based explanation), home_motivation (motivation of the home team ranging 0 to 10), away_motivation (motivation of the away team ranging 0 to 10), home_class (class of the home team ranging 0 to 10), away_class (class of the away team ranging 0 to 10), influenced_by (another game or games that can potentially influence the outcome of this game, null if there's no such games),  influenced_by_event_ids (event_ids of the games from the JSON that potentially influence the outcome of this game, null if there's none).
TXT;
    }

    private function handicapInstructionParagraph(): string
    {
        return <<<'TXT'
Handicap note: Handicap markets in this export use European-style settlement. The handicap is applied to the full-time score and every selection wins or loses — there is no push and the stake is never returned on a draw after the handicap is applied. Example: for Handicap Home +1, if the home team loses 0-1 (a one-goal defeat), the adjusted score is 1-1. That is a draw on the handicap line, but under European rules the bet still loses; the stake is not returned. Compare this to Asian handicap, where that result would often void the bet — here it does not.

TXT;
    }

    private function fifaRankingsInstructionParagraph(bool $hasRegularTournaments, bool $hasPlayoffTournaments): string
    {
        $contextParts = [];
        if ($hasRegularTournaments) {
            $contextParts[] = 'standings';
        }
        if ($hasPlayoffTournaments) {
            $contextParts[] = 'game_history';
        }

        $contextLabel = $contextParts === []
            ? 'match context'
            : implode(' and ', $contextParts);

        return 'If an event object includes "homeTeam" and/or "awayTeam" with "fifa_rank" and "fifa_points", those fields contain the official FIFA men\'s world ranking position and points for that national team. Use them as a signal of relative team strength when analysing fixtures, alongside '.$contextLabel.' and odds where present.';
    }

    private function contextAnalysisInstructionParagraphWithOdds(bool $hasRegularTournaments, bool $hasPlayoffTournaments): string
    {
        $parts = [];

        if ($hasRegularTournaments) {
            $parts[] = 'If a tournament group includes a "standings" array, you must use it before choosing a bet for fixtures in that group. Each row describes one club in the competition table: league position, team name, matches played and results (won/drawn/lost), goals for and against, goal difference, total points, a textual "outcome" describing what that position currently means (e.g. title race, European qualification, mid-table, relegation), an "outcome_positivity" score reflecting how desirable or urgent that situation is (higher for title or European chase, lower or negative for relegation danger), plus "remaining_games" and "potential_points" showing how much can still change in the table. Match each side in the fixture (from event name and context) to its standings row. Infer motivation and likely approach: teams fighting for the title or a European spot often press for wins; those in a relegation battle may be desperate or tight; clubs with little left to play for may rotate, conserve energy, or lack intensity; late-season gaps between points and potential_points reveal whether a result is must-win, enough for a draw, or largely irrelevant. Weave this motivation analysis into your probability estimates and into the explanation when standings are present. If "standings" is missing or empty for a non-playoff competition, state that limitation briefly and rely on odds and match context only.';
        }

        if ($hasPlayoffTournaments) {
            $parts[] = 'For tournament groups with "isPlayoff": true, use the "game_history" array instead of standings. Each entry is one team with a list of recent games. Every game has result (win, loss, or draw), summary (readable text such as "Won 2-0 against Rivals"), score, opponent, goals_scored, and goals_conceded for that match. Each team entry also includes aggregate goals_scored and goals_conceded across those recent games. Match each side in the fixture to its team entry and use recent form, scoring rate, and defensive record from game_history when estimating probabilities and writing your explanation. If game_history is missing or empty for a playoff competition, state that limitation briefly and rely on odds and match context only.';
        }

        if ($parts === []) {
            return '';
        }

        return implode("\n", $parts)."\n";
    }

    private function contextAnalysisInstructionParagraphWithoutOdds(bool $hasRegularTournaments, bool $hasPlayoffTournaments): string
    {
        $parts = [];

        if ($hasRegularTournaments) {
            $parts[] = 'For each non-playoff game, state the most likely outcome (home win, draw, or away win) and approximate goals scored based primarily on the tournament standings. Analyse the standings thoroughly: match each club in the fixture to its table row and infer motivation (title race, European qualification, relegation fight, mid-table comfort, dead rubbers, etc.) using position, points, goal difference, remaining games, potential points, and the outcome / outcome_positivity fields where present. When analysing motivation of each team, also consider other teams who have plus-minus the same amount of points and a similar outcome based on their position in the table. If there is some other game or games provided in the list of events that can influence this game in any way, please mention them in the description and in the "influenced_by" field. Explain your reasoning per fixture in terms of what each team is playing for and how that shapes the probable result. If standings are missing or empty for a non-playoff competition, say so briefly and use only the fixture names and any other context in the JSON.';
        }

        if ($hasPlayoffTournaments) {
            $parts[] = 'For each playoff game (tournament groups with "isPlayoff": true), state the most likely outcome and approximate goals using the "game_history" array instead of standings. Match each club to its team entry and analyse recent games: result, summary, score, opponent, per-game goals_scored and goals_conceded, plus the team totals goals_scored and goals_conceded. Use that recent form to judge attacking and defensive strength. If game_history is missing or empty for a playoff competition, say so briefly and use only the fixture names and any other context in the JSON.';
        }

        if ($parts === []) {
            return '';
        }

        return implode("\n", $parts)."\n";
    }

    /**
     * @param  list<string>  $playoffTournamentNames
     */
    private function playoffTournamentsInstructionParagraph(array $playoffTournamentNames): string
    {
        $playoffTournamentNames = array_values(array_unique(array_filter(
            $playoffTournamentNames,
            fn (string $name): bool => trim($name) !== ''
        )));

        if ($playoffTournamentNames === []) {
            return '';
        }

        $list = implode(', ', array_map(
            fn (string $name): string => '"'.$name.'"',
            $playoffTournamentNames
        ));

        return 'Playoff notice: The following tournament(s) are playoff rounds: '.$list.'. Tournament groups with "isPlayoff": true include "game_history" instead of standings.'."\n\n";
    }
}
