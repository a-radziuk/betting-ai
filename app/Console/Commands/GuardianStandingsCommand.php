<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Services\GuardianStandingsParser;
use App\Support\StandingsMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class GuardianStandingsCommand extends Command
{
    protected $signature = 'guardian:standings
        {tournamentId : Tournament primary key}';

    protected $description = 'Fetch Guardian league table HTML, parse standings JSON, and save to the tournament';

    public function handle(GuardianStandingsParser $parser): int
    {
        $tournamentId = (int) $this->argument('tournamentId');
        $tournament = Tournament::query()->find($tournamentId);

        if ($tournament === null) {
            $this->components->error("Tournament {$tournamentId} not found.");

            return self::FAILURE;
        }

        $url = trim((string) $tournament->guardian_standings_url);
        if ($url === '') {
            $this->components->error("Tournament {$tournamentId} has no guardian_standings_url set.");

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
            $data = $parser->parseHtml($response->body());
        } catch (Throwable $e) {
            $this->components->error('Failed to parse standings: '.$e->getMessage());

            return self::FAILURE;
        }

        $previousStandings = $tournament->standings;
        $hasPreviousRows = is_array($previousStandings)
            && isset($previousStandings['rows'])
            && is_array($previousStandings['rows'])
            && count($previousStandings['rows']) > 0;

        $data = StandingsMovement::apply($data, $hasPreviousRows ? $previousStandings : null);

        $tournament->standings = $data;
        $tournament->standings_updated_at = now();
        $tournament->save();

        $count = count($data['rows'] ?? []);
        $this->components->info("Saved {$count} row(s) to tournament {$tournamentId} standings.");

        return self::SUCCESS;
    }
}
