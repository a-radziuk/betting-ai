<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use JsonException;

class FifaRankingsExportCommand extends Command
{
    protected $signature = 'fifa:rankings-export';

    protected $description = 'Export all teams with fifa_rank and fifa_points to storage/exports/fifa_rankings.json';

    public function handle(): int
    {
        if (! Schema::hasTable('teams')) {
            $this->components->error('The teams table does not exist.');

            return self::FAILURE;
        }

        $teams = Team::query()
            ->whereNotNull('fifa_rank')
            ->whereNotNull('fifa_points')
            ->orderBy('fifa_rank')
            ->orderBy('id')
            ->get(['id', 'fifa_rank', 'fifa_points']);

        $payload = $teams
            ->map(fn (Team $team): array => [
                'id' => $team->id,
                'fifa_rank' => (int) $team->fifa_rank,
                'fifa_points' => round((float) $team->fifa_points, 2),
            ])
            ->values()
            ->all();

        $directory = storage_path('exports');
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            $this->components->error("Failed to create {$directory}.");

            return self::FAILURE;
        }

        $path = $directory.DIRECTORY_SEPARATOR.'fifa_rankings.json';

        try {
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->components->error('Failed to encode export JSON: '.$e->getMessage());

            return self::FAILURE;
        }

        if (file_put_contents($path, $json) === false) {
            $this->components->error("Failed to write {$path}.");

            return self::FAILURE;
        }

        $this->components->info('Wrote '.count($payload)." ranked team(s) to {$path}");

        return self::SUCCESS;
    }
}
