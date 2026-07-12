<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\BbcPremierLeagueScoresParser;
use App\Services\EventResultService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class BbcScrapeResultsCommand extends Command
{
    protected $signature = 'bbc:scrape-results
        {tournamentId : Tournament primary key (uses bbc_results_url from this row)}';

    protected $description = 'Scrape BBC results for the current month and settle matching unresolved events (Team.external_name + tournament country)';

    public function handle(BbcPremierLeagueScoresParser $parser, EventResultService $eventResultService): int
    {
        $tournamentId = (int) $this->argument('tournamentId');
        $tournament = Tournament::query()->find($tournamentId);

        if ($tournament === null) {
            $this->components->error("Tournament {$tournamentId} not found.");

            return self::FAILURE;
        }

        $baseUrl = rtrim(trim((string) $tournament->bbc_results_url), '/');
        if ($baseUrl === '') {
            $this->warn("Tournament {$tournamentId} has no bbc_results_url set.");

            return self::FAILURE;
        }

        $yearMonth = now()->format('Y-m');
        $url = "{$baseUrl}/{$yearMonth}";

        $this->components->info("Fetching {$url}");

        try {
            $response = Http::timeout(30)
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
            $this->components->error('BBC returned HTTP '.$response->status());

            return self::FAILURE;
        }

        try {
            $results = $parser->parseFinishedResults($response->body());
        } catch (Throwable $e) {
            $this->components->error('Failed to parse BBC page: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Found %d finished result(s) on BBC.', count($results)));

        $country = (string) $tournament->country;
        $settled = 0;

        foreach ($results as $row) {
            $homeTeam = Team::query()
                ->where('country', $country)
                ->where('external_name', $row['homeName'])
                ->first();

            if ($homeTeam === null) {
                $this->warn('No team with country='.$country." and external_name matching BBC home team \"{$row['homeName']}\".");

                continue;
            }

            $awayTeam = Team::query()
                ->where('country', $country)
                ->where('external_name', $row['awayName'])
                ->first();

            if ($awayTeam === null) {
                $this->warn('No team with country='.$country." and external_name matching BBC away team \"{$row['awayName']}\".");

                continue;
            }

            $scoreString = $row['homeGoals'].':'.$row['awayGoals'];

            $event = Event::query()
                ->where('tournament_id', $tournament->id)
                ->where('home_team_id', $homeTeam->id)
                ->where('away_team_id', $awayTeam->id)
                ->whereNull('score')
                ->first();

            if ($event === null) {
                $this->components->twoColumnDetail(
                    '<fg=yellow>Skip</>',
                    "No unresolved event for {$row['homeName']} vs {$row['awayName']} ({$scoreString}).",
                );

                continue;
            }

            $apply = $eventResultService->applyEventResult($event->id, $scoreString, []);

            if (! $apply['ok']) {
                $this->components->error("Event {$event->id}: {$apply['message']}");

                continue;
            }

            $this->components->twoColumnDetail(
                '<fg=green>Settled</>',
                "Event {$event->id} {$row['homeName']} vs {$row['awayName']} {$scoreString}",
            );
            $settled++;
        }

        $this->newLine();
        $this->components->info("Done. Settled {$settled} event(s).");

        return self::SUCCESS;
    }
}
