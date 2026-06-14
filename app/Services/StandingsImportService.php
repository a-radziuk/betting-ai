<?php

namespace App\Services;

use App\Models\Tournament;
use Carbon\Carbon;
use InvalidArgumentException;

final class StandingsImportService
{
    public function import(array $payload): Tournament
    {
        if (! array_key_exists('id', $payload)) {
            throw new InvalidArgumentException('Standings export is missing "id".');
        }

        $tournamentId = (int) $payload['id'];
        if ($tournamentId <= 0) {
            throw new InvalidArgumentException('Standings export has an invalid tournament id.');
        }

        $tournament = Tournament::query()->find($tournamentId);
        if ($tournament === null) {
            throw new InvalidArgumentException("Tournament {$tournamentId} not found.");
        }

        if (array_key_exists('standings', $payload)) {
            $standings = $payload['standings'];
            if ($standings !== null && ! is_array($standings)) {
                throw new InvalidArgumentException('Standings export "standings" must be an object or null.');
            }
            $tournament->standings = $standings;
        }

        if (array_key_exists('standings_promrel', $payload)) {
            $promrel = $payload['standings_promrel'];
            if ($promrel !== null && ! is_array($promrel)) {
                throw new InvalidArgumentException('Standings export "standings_promrel" must be an object or null.');
            }
            $tournament->standings_promrel = $promrel;
        }

        if (array_key_exists('standings_updated_at', $payload)) {
            $tournament->standings_updated_at = $this->parseStandingsUpdatedAt($payload['standings_updated_at']);
        } elseif (array_key_exists('standings', $payload)) {
            $tournament->standings_updated_at = now();
        }

        $tournament->save();

        return $tournament;
    }

    private function parseStandingsUpdatedAt(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException('Standings export "standings_updated_at" must be a datetime string or null.');
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Standings export "standings_updated_at" is not a valid datetime.');
        }
    }
}
