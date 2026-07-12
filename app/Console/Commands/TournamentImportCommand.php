<?php

namespace App\Console\Commands;

use App\Services\TournamentImportService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;

class TournamentImportCommand extends Command
{
    protected $signature = 'tournament:import
        {file : Absolute or project-relative path to a JSON file (same format as tournament:export)}';

    protected $description = 'Import a tournament and its teams from a JSON file';

    public function handle(TournamentImportService $importService): int
    {
        $rawPath = $this->argument('file');
        $path = $this->resolveImportPath($rawPath);

        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File not found or not readable: {$rawPath}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InvalidArgumentException("Could not read file: {$path}");
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON: '.$e->getMessage(), 0, $e);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Invalid JSON.');
        }

        try {
            $result = $importService->import($payload, skipExisting: true);
        } catch (InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $tournamentId = $payload['tournament']['id'] ?? null;

        if (! $result['imported']) {
            $this->components->warn("Skipped tournament {$tournamentId} (already exists).");

            return self::SUCCESS;
        }

        foreach ($result['skipped_team_ids'] as $teamId) {
            $this->components->warn("Skipped team {$teamId} (already exists).");
        }

        $this->components->info("Imported tournament and {$result['teams']} team(s).");

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
