<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\FifaRankingsService;
use Illuminate\Console\Command;
use Throwable;

class FifaRankingsCommand extends Command
{
    protected $signature = 'fifa:rankings';

    protected $description = 'Fetch FIFA men\'s world rankings and update team fifa_rank and fifa_points by fifa_name';

    public function handle(FifaRankingsService $rankingsService): int
    {
        $this->components->info('Fetching rankings from '.FifaRankingsService::MEN_RANKING_PAGE_URL);

        try {
            $rankings = $rankingsService->fetchMenRankings();
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf('Found %d ranked team(s) on FIFA.', count($rankings)));

        $updatedTeams = 0;
        $skippedTeams = 0;

        foreach ($rankings as $entry) {
            $matched = Team::query()
                ->where('fifa_name', $entry['name'])
                ->update([
                    'fifa_rank' => $entry['rank'],
                    'fifa_points' => $entry['points'],
                ]);

            if ($matched === 0) {
                $skippedTeams++;
                continue;
            }

            $updatedTeams += $matched;
        }

        $this->components->info("Updated {$updatedTeams} team record(s).");
        $this->components->info("Skipped {$skippedTeams} FIFA team(s) with no matching fifa_name.");

        return self::SUCCESS;
    }
}
