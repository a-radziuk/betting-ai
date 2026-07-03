<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use JsonException;

class FifaRankingsImportCommand extends Command
{
    protected $signature = 'fifa:rankings-import
        {pathToFile : Absolute or project-relative path to a JSON file}';

    protected $description = 'Update team fifa_rank and fifa_points from a JSON file (array of {id, fifa_rank, fifa_points})';

    public function handle(): int
    {
        if (! Schema::hasTable('teams')) {
            $this->components->error('The teams table does not exist.');

            return self::FAILURE;
        }

        $rawPath = $this->argument('pathToFile');
        $path = $this->resolveImportPath($rawPath);

        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File not found or not readable: {$rawPath}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InvalidArgumentException("Could not read file: {$path}");
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON: '.$e->getMessage(), 0, $e);
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('JSON root must be a list (JSON array) of objects.');
        }

        $updated = 0;
        $skipped = 0;

        foreach ($decoded as $index => $entry) {
            if (! is_array($entry)) {
                $this->components->warn('Skipping row '.($index + 1).': expected an object.');
                $skipped++;
                continue;
            }

            if (! array_key_exists('id', $entry) || ! array_key_exists('fifa_rank', $entry) || ! array_key_exists('fifa_points', $entry)) {
                $this->components->warn('Skipping row '.($index + 1).': missing id, fifa_rank, or fifa_points.');
                $skipped++;
                continue;
            }

            if (! is_numeric($entry['id']) || ! is_numeric($entry['fifa_rank']) || ! is_numeric($entry['fifa_points'])) {
                $this->components->warn('Skipping row '.($index + 1).': id, fifa_rank, and fifa_points must be numeric.');
                $skipped++;
                continue;
            }

            $teamId = (int) $entry['id'];
            $fifaRank = (int) $entry['fifa_rank'];
            $fifaPoints = round((float) $entry['fifa_points'], 2);

            $matched = Team::query()
                ->whereKey($teamId)
                ->update([
                    'fifa_rank' => $fifaRank,
                    'fifa_points' => $fifaPoints,
                ]);

            if ($matched === 0) {
                $this->components->warn("Skipping row ".($index + 1).": team {$teamId} not found.");
                $skipped++;
                continue;
            }

            $updated += $matched;
        }

        $this->components->info("Updated {$updated} team record(s); skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function resolveImportPath(string $rawPath): string
    {
        if (is_file($rawPath)) {
            return $rawPath;
        }

        return base_path(trim($rawPath, '/'.DIRECTORY_SEPARATOR));
    }
}
