<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use JsonException;

class StandingsExportCommand extends Command
{
    protected $signature = 'standings:export
        {tournamentId : Tournament primary key}';

    protected $description = 'Export tournament standings and standings_promrel to storage/exports/{id}_standings.json';

    public function handle(): int
    {
        if (! Schema::hasTable('tournaments')) {
            $this->components->error('The tournaments table does not exist.');

            return self::FAILURE;
        }

        $tournament = Tournament::query()->find($this->argument('tournamentId'));
        if ($tournament === null) {
            $this->components->error('Tournament not found.');

            return self::FAILURE;
        }

        $payload = [
            'id' => $tournament->id,
            'standings' => $tournament->standings,
            'standings_promrel' => $tournament->standings_promrel,
            'standings_updated_at' => $tournament->standings_updated_at?->toIso8601String(),
        ];

        $directory = storage_path('exports');
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            $this->components->error("Failed to create {$directory}.");

            return self::FAILURE;
        }

        $path = $directory.DIRECTORY_SEPARATOR.$tournament->id.'_standings.json';

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

        $this->components->info("Wrote standings export for tournament {$tournament->id} to {$path}");

        return self::SUCCESS;
    }
}
