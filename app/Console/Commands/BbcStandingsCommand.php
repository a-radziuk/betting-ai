<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SyncsBbcStandings;
use App\Models\Tournament;
use App\Services\BbcStandingsParser;
use App\Support\StandingsMovement;
use Illuminate\Console\Command;
use Throwable;

class BbcStandingsCommand extends Command
{
    use SyncsBbcStandings;

    protected $signature = 'bbc:standings
        {tournamentId : Tournament primary key}';

    protected $description = 'Fetch BBC league table HTML, parse standings JSON, and save to the tournament';

    public function handle(BbcStandingsParser $parser): int
    {
        $tournamentId = (int) $this->argument('tournamentId');
        $tournament = Tournament::query()->find($tournamentId);

        if ($tournament === null) {
            $this->components->error("Tournament {$tournamentId} not found.");

            return self::FAILURE;
        }

        $url = rtrim(trim((string) $tournament->bbc_standings_url), '/');
        if ($url === '') {
            $this->components->error("Tournament {$tournamentId} has no bbc_standings_url set.");

            return self::FAILURE;
        }

        $html = $this->fetchBbcStandingsHtml($url);
        if ($html === null) {
            return self::FAILURE;
        }

        try {
            $data = $parser->parseHtml($html);
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
        $data = $this->attachTeamIdsToStandingsRows($data, $tournamentId);

        $tournament->standings = $data;
        $tournament->standings_updated_at = now();
        $tournament->save();

        $count = count($data['rows'] ?? []);
        $this->components->info("Saved {$count} row(s) to tournament {$tournamentId} standings.");

        return self::SUCCESS;
    }
}
