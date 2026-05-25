<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Tournament;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use JsonException;

class TournamentExportCommand extends Command
{
    protected $signature = 'tournament:export
        {tournamentId : Tournament primary key}';

    protected $description = 'Export one tournament and all teams with the same country to storage/exports/{id}_tournament.json';

    public function handle(): int
    {
        if (! Schema::hasTable('tournaments') || ! Schema::hasTable('teams')) {
            $this->components->error('The tournaments or teams table does not exist.');

            return self::FAILURE;
        }

        $tournament = Tournament::query()->find($this->argument('tournamentId'));
        if ($tournament === null) {
            $this->components->error('Tournament not found.');

            return self::FAILURE;
        }

        $teamsQuery = Team::query()->orderBy('name')->orderBy('id');
        if ($tournament->country === null) {
            $teamsQuery->whereNull('country');
        } else {
            $teamsQuery->where('country', $tournament->country);
        }

        $payload = [
            'tournament' => $this->exportModelAttributes($tournament),
            'teams' => $teamsQuery
                ->get()
                ->map(fn (Team $team): array => $this->exportModelAttributes($team))
                ->values()
                ->all(),
        ];

        $directory = storage_path('exports');
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            $this->components->error("Failed to create {$directory}.");

            return self::FAILURE;
        }

        $path = $directory.DIRECTORY_SEPARATOR.$tournament->id.'_tournament.json';

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

        $this->components->info('Wrote '.count($payload['teams'])." team(s) to {$path}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function exportModelAttributes(Model $model): array
    {
        $out = [];
        foreach (array_keys($model->getAttributes()) as $key) {
            $out[$key] = $this->exportAttributeValue($model->getAttribute($key));
        }

        return $out;
    }

    private function exportAttributeValue(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        return $value;
    }
}
