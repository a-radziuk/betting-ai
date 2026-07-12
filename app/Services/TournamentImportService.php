<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TournamentImportService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{imported: bool, teams: int, skipped_team_ids: list<int>}
     */
    public function import(array $payload, bool $skipExisting = false): array
    {
        $tournamentRow = $payload['tournament'] ?? null;
        $teams = $payload['teams'] ?? null;

        if (! is_array($tournamentRow) || ! is_array($teams)) {
            throw new InvalidArgumentException('JSON root must contain "tournament" object and "teams" array.');
        }

        if (! array_key_exists('id', $tournamentRow)) {
            throw new InvalidArgumentException('Tournament payload is missing "id".');
        }

        $tournamentId = (int) $tournamentRow['id'];

        return DB::transaction(function () use ($tournamentRow, $teams, $skipExisting, $tournamentId): array {
            if ($skipExisting && Tournament::query()->whereKey($tournamentId)->exists()) {
                return [
                    'imported' => false,
                    'teams' => 0,
                    'skipped_team_ids' => [],
                ];
            }

            $tournament = $this->upsertTournament($tournamentRow);

            $teamCount = 0;
            $skippedTeamIds = [];
            foreach ($teams as $teamRow) {
                if (! is_array($teamRow)) {
                    continue;
                }

                if (! array_key_exists('id', $teamRow)) {
                    throw new InvalidArgumentException('Team payload is missing "id".');
                }

                $teamId = (int) $teamRow['id'];
                if ($skipExisting && Team::query()->whereKey($teamId)->exists()) {
                    $skippedTeamIds[] = $teamId;

                    continue;
                }

                $this->upsertTeam($teamRow, $tournament);
                $teamCount++;
            }

            return [
                'imported' => true,
                'teams' => $teamCount,
                'skipped_team_ids' => $skippedTeamIds,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertTournament(array $row): Tournament
    {
        if (! array_key_exists('id', $row)) {
            throw new InvalidArgumentException('Tournament payload is missing "id".');
        }

        $id = (int) $row['id'];
        $tournament = Tournament::query()->find($id) ?? new Tournament;
        $tournament->id = $id;
        $tournament->forceFill($this->onlyKeys($row, [
            'name',
            'rank',
            'country',
            'is_playoff',
            'is_active',
            'is_fifa',
            'source',
            'export_marker',
            'stoiximan_url',
            'parimatch_url',
            'guardian_standings_url',
            'guardian_results_url',
            'bbc_standings_url',
            'bbc_results_url',
            'standings',
            'standings_updated_at',
            'standings_promrel',
            'created_at',
            'updated_at',
        ]));
        $tournament->save();

        return $tournament;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertTeam(array $row, Tournament $fallbackTournament): void
    {
        if (! array_key_exists('id', $row)) {
            throw new InvalidArgumentException('Team payload is missing "id".');
        }

        $id = (int) $row['id'];
        $team = Team::query()->find($id) ?? new Team;
        $team->id = $id;

        $attributes = $this->onlyKeys($row, [
            'tournament_id',
            'name',
            'display_name',
            'external_name',
            'short_name',
            'league',
            'country',
            'guardian_name',
            'fifa_name',
            'fifa_rank',
            'fifa_points',
            'created_at',
            'updated_at',
        ]);

        if (array_key_exists('tournament_id', $attributes) && $attributes['tournament_id'] !== null) {
            $referencedTournamentId = (int) $attributes['tournament_id'];
            if (! Tournament::query()->whereKey($referencedTournamentId)->exists()) {
                $attributes['tournament_id'] = $fallbackTournament->id;
            }
        }

        $team->forceFill($attributes);
        $team->save();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function onlyKeys(array $row, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                $out[$key] = $row[$key];
            }
        }

        return $out;
    }
}
