<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class FifaRankingsService
{
    public const MEN_RANKING_PAGE_URL = 'https://inside.fifa.com/fifa-world-ranking/men';

    private const MEN_RANKING_API_URL = 'https://api.fifa.com/api/v3/rankings?gender=1';

    /**
     * @return list<array{name: string, rank: int, points: float}>
     */
    public function fetchMenRankings(): array
    {
        $this->assertMenRankingPageReachable();

        try {
            $response = Http::timeout(30)
                ->withHeaders($this->requestHeaders())
                ->get(self::MEN_RANKING_API_URL);
        } catch (Throwable $e) {
            throw new RuntimeException('FIFA rankings API request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('FIFA rankings API returned HTTP '.$response->status().'.');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('FIFA rankings API returned invalid JSON.');
        }

        return $this->parseRankingsPayload($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{name: string, rank: int, points: float}>
     */
    public function parseRankingsPayload(array $payload): array
    {
        $results = $payload['Results'] ?? null;
        if (! is_array($results)) {
            throw new InvalidArgumentException('FIFA rankings payload is missing "Results".');
        }

        $rankings = [];

        foreach ($results as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = $this->extractTeamName($row);
            $rank = $row['Rank'] ?? null;
            $points = $row['DecimalTotalPoints'] ?? $row['TotalPoints'] ?? null;

            if ($name === null || $rank === null || $points === null) {
                continue;
            }

            $rankings[] = [
                'name' => $name,
                'rank' => (int) $rank,
                'points' => round((float) $points, 2),
            ];
        }

        if ($rankings === []) {
            throw new InvalidArgumentException('FIFA rankings payload contains no team rows.');
        }

        return $rankings;
    }

    private function assertMenRankingPageReachable(): void
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders($this->requestHeaders())
                ->get(self::MEN_RANKING_PAGE_URL);
        } catch (Throwable $e) {
            throw new RuntimeException('FIFA rankings page request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('FIFA rankings page returned HTTP '.$response->status().'.');
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function extractTeamName(array $row): ?string
    {
        $teamName = $row['TeamName'] ?? null;
        if (! is_array($teamName)) {
            return null;
        }

        foreach ($teamName as $localized) {
            if (! is_array($localized)) {
                continue;
            }

            $description = $localized['Description'] ?? null;
            if (is_string($description) && $description !== '') {
                return $description;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function requestHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Accept' => 'application/json, text/html,application/xhtml+xml',
            'Accept-Language' => 'en-GB,en;q=0.9',
            'Referer' => self::MEN_RANKING_PAGE_URL,
        ];
    }
}
