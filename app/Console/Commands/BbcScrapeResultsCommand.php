<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Team;
use App\Services\BbcPremierLeagueScoresParser;
use App\Services\EventResultService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class BbcScrapeResultsCommand extends Command
{
    protected $signature = 'bbc:scrape-results';

    protected $description = 'Scrape BBC Premier League results for the current month and settle matching unresolved events (tournament_id = 1, Team.external_name)';

    private const TOURNAMENT_ID = 1;

    public function handle(BbcPremierLeagueScoresParser $parser, EventResultService $eventResultService): int
    {
        $yearMonth = now()->format('Y-m');
        $url = "https://www.bbc.com/sport/football/premier-league/scores-fixtures/{$yearMonth}?filter=results";

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

        $settled = 0;

        foreach ($results as $row) {
            $homeTeam = Team::query()
                ->where('tournament_id', self::TOURNAMENT_ID)
                ->where('external_name', $row['homeName'])
                ->first();

            if ($homeTeam === null) {
                $this->warn('No team with tournament_id='.self::TOURNAMENT_ID." and external_name matching BBC home team \"{$row['homeName']}\".");

                continue;
            }

            $awayTeam = Team::query()
                ->where('tournament_id', self::TOURNAMENT_ID)
                ->where('external_name', $row['awayName'])
                ->first();

            if ($awayTeam === null) {
                $this->warn('No team with tournament_id='.self::TOURNAMENT_ID." and external_name matching BBC away team \"{$row['awayName']}\".");

                continue;
            }

            $scoreString = $row['homeGoals'].':'.$row['awayGoals'];

            $event = Event::query()
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
