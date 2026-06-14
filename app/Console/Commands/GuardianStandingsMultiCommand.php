<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SyncsGuardianStandings;
use App\Models\Tournament;
use App\Services\GuardianStandingsParser;
use App\Support\StandingsMovement;
use Illuminate\Console\Command;
use Throwable;

class GuardianStandingsMultiCommand extends Command
{
    use SyncsGuardianStandings;

    protected $signature = 'guardian:standings-multi
        {tournamentId : Tournament primary key}';

    protected $description = 'Fetch Guardian multi-group table HTML, parse all group standings, and save to the tournament';

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

        $html = $this->fetchGuardianStandingsHtml($url);
        if ($html === null) {
            return self::FAILURE;
        }

        try {
            $data = $parser->parseMultiGroupHtml($html);
        } catch (Throwable $e) {
            $this->components->error('Failed to parse group standings: '.$e->getMessage());

            return self::FAILURE;
        }

        $previousStandings = $tournament->standings;
        $hasPreviousGroups = is_array($previousStandings)
            && isset($previousStandings['groups'])
            && is_array($previousStandings['groups'])
            && count($previousStandings['groups']) > 0;

        $data = StandingsMovement::applyToGroups($data, $hasPreviousGroups ? $previousStandings : null);
        $data = $this->attachTeamIdsToStandingsGroups($data, $tournamentId);

        $tournament->standings = $data;
        $tournament->standings_updated_at = now();
        $tournament->save();

        $groupCount = count($data['groups'] ?? []);
        $rowCount = array_sum(array_map(
            static fn (array $group): int => count($group['rows'] ?? []),
            $data['groups'] ?? [],
        ));

        $this->components->info("Saved {$groupCount} group(s) with {$rowCount} row(s) to tournament {$tournamentId} standings.");

        return self::SUCCESS;
    }
}
