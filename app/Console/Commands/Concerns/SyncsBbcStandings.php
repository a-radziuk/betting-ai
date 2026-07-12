<?php

namespace App\Console\Commands\Concerns;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Facades\Http;
use Throwable;

trait SyncsBbcStandings
{
    protected function fetchBbcStandingsHtml(string $url): ?string
    {
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

            return null;
        }

        if (! $response->successful()) {
            $this->components->error('BBC returned HTTP '.$response->status());

            return null;
        }

        return $response->body();
    }

    /**
     * @param  array{rows?: list<array<string, mixed>>}  $data
     * @return array{rows?: list<array<string, mixed>>}
     */
    protected function attachTeamIdsToStandingsRows(array $data, int $tournamentId): array
    {
        $rows = $data['rows'] ?? null;
        if (! is_array($rows) || $rows === []) {
            return $data;
        }

        $data['rows'] = $this->attachTeamIdsToRowList($rows, $tournamentId);

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function attachTeamIdsToRowList(array $rows, int $tournamentId): array
    {
        $tournament = Tournament::query()->find($tournamentId);

        $teamIdsByLabel = [];
        $teamNamesByLabel = [];
        $teams = Team::query()
            ->where('country', $tournament->country)
            ->whereNotNull('external_name')
            ->orderBy('id')
            ->get(['id', 'external_name', 'display_name', 'name']);

        foreach ($teams as $team) {
            $key = mb_strtolower(trim((string) $team->external_name));
            if ($key === '') {
                continue;
            }
            if (! array_key_exists($key, $teamIdsByLabel)) {
                $teamIdsByLabel[$key] = $team->id;
            }
            if (! array_key_exists($key, $teamNamesByLabel)) {
                $teamNamesByLabel[$key] = $team->display_name ?: $team->name;
            }
        }

        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = isset($row['team']) ? trim((string) $row['team']) : '';
            $lookup = $label === '' ? '' : mb_strtolower($label);
            $rows[$i]['team_id'] = $teamIdsByLabel[$lookup] ?? null;
            $rows[$i]['team_display_name'] = $teamNamesByLabel[$lookup] ?? null;
        }

        return $rows;
    }
}
