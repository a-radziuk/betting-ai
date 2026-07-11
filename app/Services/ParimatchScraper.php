<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

class ParimatchScraper
{
    private const ID_PREFIX = 9_000_000_000_000_000_000;

    /**
     * @return array{events:int,markets:int,selections:int,odds:int}
     */
    public function scrapeNearestEvents(Tournament $tournament, int $limit = 20): array
    {
        $competitionUrl = trim((string) $tournament->parimatch_url);
        if ($competitionUrl === '') {
            throw new RuntimeException('Tournament has no parimatch_url set.');
        }

        $payload = $this->fetchPayload($competitionUrl, $limit);
        $events = $payload['events'] ?? [];

        if (! is_array($events) || $events === []) {
            throw new RuntimeException('No upcoming Parimatch events were found to scrape.');
        }

        $stats = ['events' => 0, 'markets' => 0, 'selections' => 0, 'odds' => 0];

        foreach ($events as $eventData) {
            if (! is_array($eventData)) {
                continue;
            }

            DB::transaction(function () use ($eventData, $tournament, &$stats): void {
                $homeTeamName = trim((string) ($eventData['home_team'] ?? ''));
                $awayTeamName = trim((string) ($eventData['away_team'] ?? ''));
                $externalId = trim((string) ($eventData['external_id'] ?? ''));

                if ($homeTeamName === '' || $awayTeamName === '' || $externalId === '') {
                    return;
                }

                $homeTeam = $this->findOrCreateTeam($homeTeamName, $tournament);
                $awayTeam = $this->findOrCreateTeam($awayTeamName, $tournament);
                $eventId = $this->toBigIntId('event', $externalId);

                Event::query()->updateOrCreate(
                    ['id' => $eventId],
                    [
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'tournament_id' => $tournament->id,
                        'source' => Event::SOURCE_PARIMATCH,
                        'start_time' => Carbon::parse((string) ($eventData['start_time'] ?? now()->addDay())),
                        'status' => Event::STATUS_SCHEDULED,
                    ]
                );

                $this->clearEventMarkets($eventId);
                $stats['events']++;

                $markets = is_array($eventData['markets'] ?? null) ? $eventData['markets'] : [];

                foreach ($this->deduplicateMarketsForEvent($markets, $homeTeamName, $awayTeamName) as $marketData) {
                    $type = $this->normalizeMarketType(
                        (string) ($marketData['type'] ?? ''),
                        $homeTeamName,
                        $awayTeamName,
                    );

                    if (! in_array($type, $this->supportedMarketTypes(), true)) {
                        continue;
                    }

                    $period = (string) ($marketData['period'] ?? Market::PERIOD_FULL_TIME);

                    $marketId = $this->toBigIntId(
                        'market',
                        $externalId.'|'.($marketData['external_id'] ?? $type)
                    );

                    Market::query()->create([
                        'id' => $marketId,
                        'event_id' => $eventId,
                        'type' => $type,
                        'period' => $period,
                        'line' => null,
                        'status' => Market::STATUS_OPEN,
                        'is_supported_market' => true,
                    ]);
                    $stats['markets']++;

                    foreach ($marketData['selections'] as $selectionData) {
                        if (! is_array($selectionData)) {
                            continue;
                        }

                        $selectionName = $this->normalizeSelectionName((string) ($selectionData['name'] ?? ''));
                        $selectionId = $this->toBigIntId(
                            'selection',
                            $externalId.'|'.($marketData['external_id'] ?? $type).'|'.($selectionData['external_id'] ?? $selectionName)
                        );

                        $lineValue = isset($selectionData['handicap']) && is_numeric($selectionData['handicap'])
                            ? (float) $selectionData['handicap']
                            : null;

                        $value = null;
                        $handicap = null;

                        if (in_array($type, [
                            Market::TYPE_TOTAL_ASIAN,
                            Market::TYPE_HOME_TOTAL_ASIAN,
                            Market::TYPE_AWAY_TOTAL_ASIAN,
                            Market::TYPE_HANDICAP_ASIAN,
                        ], true)) {
                            $value = $lineValue;
                        } else {
                            $handicap = $lineValue;
                        }

                        Selection::query()->create([
                            'id' => $selectionId,
                            'market_id' => $marketId,
                            'name' => $selectionName,
                            'participant_id' => null,
                            'handicap' => $handicap,
                            'value' => $value,
                            'created_at' => now(),
                        ]);
                        $stats['selections']++;

                        $price = (float) ($selectionData['odds'] ?? 0);
                        if ($price <= 1) {
                            continue;
                        }

                        Odd::query()->create([
                            'id' => $this->toBigIntId('odd', (string) $selectionId),
                            'selection_id' => $selectionId,
                            'odds' => $price,
                            'probability' => round(1 / $price, 4),
                            'is_active' => true,
                            'created_at' => now(),
                        ]);
                        $stats['odds']++;
                    }
                }
            });
        }

        return $stats;
    }

    /**
     * @return array{events: array<int, array<string, mixed>>}
     */
    public function fetchPayload(string $url, int $limit = 20): array
    {
        $scriptPath = base_path('scripts/parimatch-scrape.mjs');
        if (! file_exists($scriptPath)) {
            throw new RuntimeException('Playwright scrape script not found: '.$scriptPath);
        }

        $process = new Process(['node', $scriptPath, $url, (string) max(1, $limit)], base_path());
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'Parimatch browser scrape failed: '.trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        $decoded = json_decode($process->getOutput(), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Parimatch scrape returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @param  array<int, array<string, mixed>>  $markets
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateMarketsForEvent(array $markets, string $homeTeam, string $awayTeam): array
    {
        $unique = [];
        $seen = [];

        foreach ($markets as $marketData) {
            $type = $this->normalizeMarketType((string) ($marketData['type'] ?? ''), $homeTeam, $awayTeam);
            $period = (string) ($marketData['period'] ?? Market::PERIOD_FULL_TIME);
            $externalId = (string) ($marketData['external_id'] ?? $type);
            $key = $type.'|'.$period.'|'.$externalId;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $marketData;
        }

        return $unique;
    }

    private function normalizeMarketType(string $type, string $homeTeam, string $awayTeam): string
    {
        $normalized = strtoupper(trim($type));
        $home = strtoupper(trim($homeTeam));
        $away = strtoupper(trim($awayTeam));

        $map = [
            'FULL-TIME RESULT' => Market::TYPE_MATCH_RESULT,
            'FULL TIME RESULT' => Market::TYPE_MATCH_RESULT,
            'DOUBLE CHANCE' => Market::TYPE_DOUBLE_CHANCE,
            'TOTAL' => Market::TYPE_TOTAL_ASIAN,
            'BOTH TEAMS TO SCORE' => Market::TYPE_BTTS,
            'HANDICAP' => Market::TYPE_HANDICAP_ASIAN,
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        if ($home !== '' && $normalized === $home.' TOTAL') {
            return Market::TYPE_HOME_TOTAL_ASIAN;
        }

        if ($away !== '' && $normalized === $away.' TOTAL') {
            return Market::TYPE_AWAY_TOTAL_ASIAN;
        }

        if ($home !== '' && $normalized === $home.' TO SCORE A GOAL') {
            return Market::TYPE_HOME_TO_SCORE;
        }

        if ($away !== '' && $normalized === $away.' TO SCORE A GOAL') {
            return Market::TYPE_AWAY_TO_SCORE;
        }

        return $normalized;
    }

    private function normalizeSelectionName(string $name): string
    {
        $upper = strtoupper(trim($name));

        return match ($upper) {
            '1' => Selection::NAME_HOME,
            'X' => Selection::NAME_DRAW,
            '2' => Selection::NAME_AWAY,
            default => $upper,
        };
    }

    /**
     * @return list<string>
     */
    private function supportedMarketTypes(): array
    {
        return [
            Market::TYPE_MATCH_RESULT,
            Market::TYPE_DOUBLE_CHANCE,
            Market::TYPE_TOTAL_ASIAN,
            Market::TYPE_HOME_TOTAL_ASIAN,
            Market::TYPE_AWAY_TOTAL_ASIAN,
            Market::TYPE_BTTS,
            Market::TYPE_HOME_TO_SCORE,
            Market::TYPE_AWAY_TO_SCORE,
            Market::TYPE_HANDICAP_ASIAN,
        ];
    }

    private function findOrCreateTeam(string $name, Tournament $tournament): Team
    {
        return Team::query()->firstOrCreate(
            ['name' => $name],
            [
                'short_name' => mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $name) ?? $name, 0, 3)),
                'league' => $tournament->name,
                'country' => $tournament->country,
            ]
        );
    }

    private function clearEventMarkets(int $eventId): void
    {
        $marketIds = Market::query()->where('event_id', $eventId)->pluck('id');
        $selectionIds = Selection::query()->whereIn('market_id', $marketIds)->pluck('id');

        Odd::query()->whereIn('selection_id', $selectionIds)->delete();
        Selection::query()->whereIn('market_id', $marketIds)->delete();
        Market::query()->where('event_id', $eventId)->delete();
    }

    private function toBigIntId(string $scope, string $rawId): int
    {
        $hex = substr(sha1('parimatch|'.$scope.'|'.$rawId), 0, 14);

        return self::ID_PREFIX + (int) hexdec($hex);
    }
}
