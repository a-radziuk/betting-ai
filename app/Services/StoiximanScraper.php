<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

class StoiximanScraper
{
    private const COMPETITION_URL = 'https://en.stoiximan.com.cy/sport/soccer/competitions/england/1/';

    /**
     * Scrape nearest events and persist everything.
     *
     * @return array{events:int,markets:int,selections:int,odds:int}
     */
    public function scrapeNearestEvents(int $limit = 20): array
    {
        $competitionHtml = $this->fetchHtml(self::COMPETITION_URL);
        $eventUrls = $this->extractEventUrls($competitionHtml)->take($limit)->values();

        if ($eventUrls->isEmpty()) {
            throw new RuntimeException(
                'No event links were found on Stoiximan competition page. The site may be protected by Cloudflare challenge.'
            );
        }

        $stats = ['events' => 0, 'markets' => 0, 'selections' => 0, 'odds' => 0];

        foreach ($eventUrls as $eventUrl) {
            $eventHtml = $this->fetchHtml($eventUrl);
            $parsed = $this->parseEventPage($eventHtml, $eventUrl);

            if ($parsed === null) {
                continue;
            }

            DB::transaction(function () use ($parsed, &$stats): void {
                $homeTeam = $this->findOrCreateTeam($parsed['home_team']);
                $awayTeam = $this->findOrCreateTeam($parsed['away_team']);
                $eventId = $this->toBigIntId('event', $parsed['external_id']);

                Event::query()->updateOrCreate(
                    ['id' => $eventId],
                    [
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'start_time' => $parsed['start_time'],
                        'status' => Event::STATUS_SCHEDULED,
                    ]
                );

                $this->clearEventMarkets($eventId);
                $stats['events']++;

                foreach ($parsed['markets'] as $marketData) {
                    $marketId = $this->toBigIntId('market', $parsed['external_id'].'|'.$marketData['external_id']);

                    $type = $this->normalizeMarketType($marketData['type']);
                    Market::query()->create([
                        'id' => $marketId,
                        'event_id' => $eventId,
                        'type' => $type,
                        'period' => $marketData['period'] ?? Market::PERIOD_FULL_TIME,
                        'line' => $marketData['line'],
                        'status' => Market::STATUS_OPEN,
                        'is_supported_market' => in_array($type, Market::SUPPORTED_TYPES),
                    ]);
                    $stats['markets']++;

                    foreach ($marketData['selections'] as $selectionData) {
                        $selectionId = $this->toBigIntId(
                            'selection',
                            $parsed['external_id'].'|'.$marketData['external_id'].'|'.$selectionData['external_id']
                        );
                        Selection::query()->create([
                            'id' => $selectionId,
                            'market_id' => $marketId,
                            'name' => $this->normalizeSelectionName($selectionData['name']),
                            'participant_id' => null,
                            'handicap' => $selectionData['handicap'],
                            'created_at' => now(),
                        ]);
                        $stats['selections']++;

                        $price = (float) $selectionData['odds'];
                        Odd::query()->create([
                            'id' => $this->toBigIntId('odd', (string) $selectionId),
                            'selection_id' => $selectionId,
                            'odds' => $price,
                            'probability' => $price > 0 ? round(1 / $price, 4) : null,
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

    private function fetchHtml(string $url): string
    {
        $scriptPath = base_path('scripts/stoiximan-fetch.mjs');
        if (! file_exists($scriptPath)) {
            throw new RuntimeException('Playwright fetch script not found: '.$scriptPath);
        }

        $process = new Process(['node', $scriptPath, $url], base_path());
        $process->setTimeout(150);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'Browser fetch failed for '.$url.': '.trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        $html = $process->getOutput();
        if (trim($html) === '') {
            throw new RuntimeException('Browser fetch returned empty HTML for '.$url);
        }

        return $html;
    }

    /**
     * @return Collection<int, string>
     */
    private function extractEventUrls(string $html): Collection
    {
        preg_match_all(
            '#https://en\.stoiximan\.com\.cy/(?:sport/[^"\']+/event/[^"\']+|match-odds/[^"\']+)#i',
            $html,
            $matches
        );

        return collect($matches[0] ?? [])
            ->map(fn (string $url) => preg_replace('/\?.*$/', '', $url) ?? $url)
            ->unique()
            ->values();
    }

    /**
     * Parse an event page.
     *
     * This parser is intentionally defensive:
     * - It first tries JSON-LD.
     * - Then it tries script blobs containing "market"/"odds" structures.
     *
     * @return array{
     *   external_id:string,
     *   home_team:string,
     *   away_team:string,
     *   start_time:Carbon,
     *   markets:array<int,array{
     *      external_id:string,
     *      type:string,
     *      period:string,
     *      line:float|null,
     *      selections:array<int,array{
     *          external_id:string,
     *          name:string,
     *          odds:float,
     *          handicap:float|null
     *      }>
     *   }>
     * }|null
     */
    private function parseEventPage(string $html, string $url): ?array
    {
        $externalId = $this->extractExternalIdFromUrl($url);
        $matchName = $this->extractMatchNameFromJsonLd($html);
        $startTime = $this->extractStartTimeFromJsonLd($html) ?? now()->addHours(6);

        if ($matchName === null) {
            return null;
        }

        [$home, $away] = $this->splitMatchName($matchName);
        $markets = $this->extractMarketsFromEmbeddedJson($html);

        if ($markets === []) {
            return null;
        }

        return [
            'external_id' => $externalId,
            'home_team' => $home,
            'away_team' => $away,
            'start_time' => $startTime,
            'markets' => $markets,
        ];
    }

    private function extractExternalIdFromUrl(string $url): string
    {
        if (preg_match('/event\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\/(\d+)\/?$/', $url, $matches)) {
            return $matches[1];
        }

        return md5($url);
    }

    private function extractMatchNameFromJsonLd(string $html): ?string
    {
        if (! preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $match)) {
            return null;
        }

        $decoded = json_decode(trim($match[1]), true);

        if (! is_array($decoded)) {
            return null;
        }

        return $decoded['name'] ?? null;
    }

    private function extractStartTimeFromJsonLd(string $html): ?Carbon
    {
        if (! preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $match)) {
            return null;
        }

        $decoded = json_decode(trim($match[1]), true);

        if (! is_array($decoded) || ! isset($decoded['startDate'])) {
            return null;
        }

        return Carbon::parse((string) $decoded['startDate']);
    }

    /**
     * Extract markets from embedded JSON blobs.
     *
     * @return array<int, array{
     *   external_id:string,
     *   type:string,
     *   period:string,
     *   line:float|null,
     *   selections:array<int,array{
     *      external_id:string,
     *      name:string,
     *      odds:float,
     *      handicap:float|null
     *   }>
     * }>
     */
    private function extractMarketsFromEmbeddedJson(string $html): array
    {
        preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $scripts);

        $markets = [];

        foreach ($scripts[1] ?? [] as $scriptContent) {
            if (! str_contains($scriptContent, 'odds') || ! str_contains($scriptContent, 'market')) {
                continue;
            }

            $json = $this->extractFirstJsonObject($scriptContent);
            if ($json === null) {
                continue;
            }

            $decoded = json_decode($json, true);
            if (! is_array($decoded)) {
                continue;
            }

            $fromBlob = $this->mapMarketsRecursively($decoded);
            if ($fromBlob !== []) {
                $markets = array_merge($markets, $fromBlob);
            }
        }

        return $this->uniqueMarkets($markets);
    }

    private function extractFirstJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $length = strlen($text);
        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    /**
     * @param array<mixed> $node
     * @return array<int, array{
     *   external_id:string,
     *   type:string,
     *   period:string,
     *   line:float|null,
     *   selections:array<int,array{
     *      external_id:string,
     *      name:string,
     *      odds:float,
     *      handicap:float|null
     *   }>
     * }>
     */
    private function mapMarketsRecursively(array $node): array
    {
        $markets = [];

        if (isset($node['name']) && is_string($node['name'])) {
            $marketSelections = $this->extractSelectionsFromMarketNode($node);

            if ($marketSelections !== []) {
                $markets[] = [
                    'external_id' => (string) ($node['id'] ?? md5((string) $node['name'])),
                    'type' => (string) $node['name'],
                    'period' => (string) ($node['period'] ?? Market::PERIOD_FULL_TIME),
                    'line' => isset($node['line']) && is_numeric($node['line']) ? (float) $node['line'] : null,
                    'selections' => $marketSelections,
                ];
            }
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $markets = array_merge($markets, $this->mapMarketsRecursively($value));
            }
        }

        return $markets;
    }

    /**
     * @param array<mixed> $marketNode
     * @return array<int,array{
     *   external_id:string,
     *   name:string,
     *   odds:float,
     *   handicap:float|null
     * }>
     */
    private function extractSelectionsFromMarketNode(array $marketNode): array
    {
        $rawSelections = [];

        if (isset($marketNode['selections']) && is_array($marketNode['selections'])) {
            $rawSelections = array_merge($rawSelections, $marketNode['selections']);
        }

        // Some Stoiximan markets (e.g. "Handicap Match Result") keep selections in tableLayout rows.
        $rows = $marketNode['tableLayout']['rows'] ?? null;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (! is_array($row) || ! isset($row['groupSelections']) || ! is_array($row['groupSelections'])) {
                    continue;
                }
                foreach ($row['groupSelections'] as $groupSelection) {
                    if (! is_array($groupSelection) || ! isset($groupSelection['selections']) || ! is_array($groupSelection['selections'])) {
                        continue;
                    }
                    $rawSelections = array_merge($rawSelections, $groupSelection['selections']);
                }
            }
        }

        $marketSelections = [];
        foreach ($rawSelections as $selection) {
            if (! is_array($selection)) {
                continue;
            }

            $odds = $selection['odds'] ?? $selection['price'] ?? null;
            $name = $selection['name'] ?? null;
            if (! is_numeric($odds) || ! is_string($name)) {
                continue;
            }

            $marketSelections[] = [
                'external_id' => (string) ($selection['id'] ?? md5($name.(string) $odds)),
                'name' => $name,
                'odds' => (float) $odds,
                'handicap' => isset($selection['handicap']) && is_numeric($selection['handicap'])
                    ? (float) $selection['handicap']
                    : null,
            ];
        }

        return $marketSelections;
    }

    /**
     * @param array<int, array{
     *   external_id:string,
     *   type:string,
     *   period:string,
     *   line:float|null,
     *   selections:array<int,array{
     *      external_id:string,
     *      name:string,
     *      odds:float,
     *      handicap:float|null
     *   }>
     * }> $markets
     * @return array<int, array{
     *   external_id:string,
     *   type:string,
     *   period:string,
     *   line:float|null,
     *   selections:array<int,array{
     *      external_id:string,
     *      name:string,
     *      odds:float,
     *      handicap:float|null
     *   }>
     * }>
     */
    private function uniqueMarkets(array $markets): array
    {
        $unique = [];
        $seen = [];
        foreach ($markets as $market) {
            $key = $market['external_id'].'|'.$market['type'].'|'.$market['period'].'|'.(string) $market['line'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $market;
        }

        return $unique;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitMatchName(string $name): array
    {
        $parts = preg_split('/\s+vs\.?\s+|\s+-\s+|\s+v\s+/i', $name) ?: [];
        if (count($parts) >= 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return [$name.' Home', $name.' Away'];
    }

    private function normalizeMarketType(string $type): string
    {
        $upper = strtoupper(trim($type));
        $normalized = preg_replace('/[^A-Z0-9]+/', ' ', $upper);
        $normalized = trim((string) $normalized);
        $tokens = preg_split('/\s+/', $normalized) ?: [];
        $hasHandicap = in_array('HANDICAP', $tokens, true);
        $hasMatch = in_array('MATCH', $tokens, true);
        $hasResult = in_array('RESULT', $tokens, true) || in_array('RESULTS', $tokens, true);
        if ($hasHandicap && ($hasMatch && $hasResult)) {
            return Market::TYPE_HANDICAP;
        }

        $map = [
            'MATCH RESULT' => Market::TYPE_MATCH_RESULT,
            '1X2' => Market::TYPE_MATCH_RESULT,
            'OVER/UNDER' => Market::TYPE_OVER_UNDER,
            'OVER UNDER' => Market::TYPE_OVER_UNDER,
            'BOTH TEAMS TO SCORE' => Market::TYPE_BTTS,
            'BTTS' => Market::TYPE_BTTS,
            'HANDICAP' => Market::TYPE_HANDICAP,
            'HANDICAP MATCH RESULT' => Market::TYPE_HANDICAP,
            'HANDICAP MATCH RESULTS' => Market::TYPE_HANDICAP,
            'MATCH RESULT HANDICAP' => Market::TYPE_HANDICAP,
            'MATCH RESULTS HANDICAP' => Market::TYPE_HANDICAP,
            '3 WAY HANDICAP' => Market::TYPE_HANDICAP,
            'CORRECT SCORE' => Market::TYPE_CORRECT_SCORE,
            'GOALSCORER' => Market::TYPE_GOALSCORER,
            'DOUBLE CHANCE' => Market::TYPE_DOUBLE_CHANCE,
        ];

        return $map[$upper] ?? $map[$normalized] ?? $normalized;
    }

    private function normalizeSelectionName(string $name): string
    {
        $upper = strtoupper(trim($name));
        $map = [
            '1' => 'HOME',
            'X' => 'DRAW',
            '2' => 'AWAY',
        ];

        return $map[$upper] ?? $upper;
    }

    private function findOrCreateTeam(string $name): Team
    {
        return Team::query()->firstOrCreate(
            ['name' => $name],
            [
                'short_name' => mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $name) ?? $name, 0, 3)),
                'league' => 'England',
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
        $hex = substr(sha1($scope.'|'.$rawId), 0, 15);

        return (int) hexdec($hex);
    }
}
