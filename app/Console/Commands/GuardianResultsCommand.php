<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\EventResultService;
use App\Services\GuardianResultsParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class GuardianResultsCommand extends Command
{
    protected $signature = 'guardian:results
        {tournamentId : Tournament primary key}';

    protected $description = 'Parse Guardian results (FT), match teams by guardian_name + tournament country, settle bets for matching events';

    public function handle(GuardianResultsParser $parser, EventResultService $eventResultService): int
    {
        $tournamentId = (int) $this->argument('tournamentId');
        $tournament = Tournament::query()->find($tournamentId);

        if ($tournament === null) {
            $this->components->error("Tournament {$tournamentId} not found.");

            return self::FAILURE;
        }

        $url = trim((string) $tournament->guardian_results_url);
        if ($url === '') {
            $this->components->error("Tournament {$tournamentId} has no guardian_results_url set.");

            return self::FAILURE;
        }

        $this->components->info("Fetching {$url}");

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'en-GB,en;q=0.9',
                ])
                ->get($url);
        } catch (Throwable $e) {
            $this->components->error('HTTP request failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->components->error('Guardian returned HTTP '.$response->status());

            return self::FAILURE;
        }

        try {
            $rows = $parser->parseHtml($response->body());
        } catch (Throwable $e) {
            $this->components->error('Failed to parse results: '.$e->getMessage());

            return self::FAILURE;
        }

        $rows = $this->dedupeRows($rows);

        $this->components->info(sprintf('Parsed %d full-time result(s).', count($rows)));

        $settled = 0;

        foreach ($rows as $row) {
            $homeTeam = $this->resolveTeam($tournament, $row['homeName']);
            if ($homeTeam === null) {
                $this->warn('No team with guardian_name + country match for home "'.$row['homeName'].'".');

                continue;
            }

            $awayTeam = $this->resolveTeam($tournament, $row['awayName']);
            if ($awayTeam === null) {
                $this->warn('No team with guardian_name + country match for away "'.$row['awayName'].'".');

                continue;
            }

            $scoreString = $row['homeGoals'].':'.$row['awayGoals'];

            $event = Event::query()
                ->where('tournament_id', $tournament->id)
                ->where('home_team_id', $homeTeam->id)
                ->where('away_team_id', $awayTeam->id)
                ->first();

            if ($event === null) {
                $this->components->twoColumnDetail(
                    '<fg=yellow>Skip</>',
                    "No event for {$row['homeName']} vs {$row['awayName']} ({$scoreString}).",
                );

                continue;
            }

            if (filled($event->score) || $event->status === Event::STATUS_FINISHED) {
                $this->components->warn("Event {$event->id} is already settled (score present or finished). Skipping.");

                continue;
            }

            $apply = $eventResultService->applyEventResult($event->id, $scoreString, []);

            if (! $apply['ok']) {
                $this->components->error("Event {$event->id}: {$apply['message']}");

                continue;
            }

            $this->components->twoColumnDetail(
                '<fg=green>Settled</>',
                "Event {$event->id} {$row['homeName']} {$scoreString} {$row['awayName']} — {$apply['message']}",
            );
            $settled++;
        }

        $this->newLine();
        $this->components->info("Done. Settled {$settled} event(s).");

        return self::SUCCESS;
    }

    /**
     * @param  list<array{homeName: string, awayName: string, homeGoals: int, awayGoals: int}>  $rows
     * @return list<array{homeName: string, awayName: string, homeGoals: int, awayGoals: int}>
     */
    private function dedupeRows(array $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $key = $row['homeName'].'|'.$row['awayName'].'|'.$row['homeGoals'].'|'.$row['awayGoals'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }

        return $out;
    }

    private function resolveTeam(Tournament $tournament, string $guardianLabel): ?Team
    {
        $label = trim($guardianLabel);
        if ($label === '') {
            return null;
        }

        return Team::query()
            ->whereNotNull('guardian_name')
            ->whereRaw('LOWER(TRIM(guardian_name)) = ?', [mb_strtolower($label)])
            ->where('country', $tournament->country)
            ->first();
    }
}
