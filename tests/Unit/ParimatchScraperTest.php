<?php

namespace Tests\Unit;

use App\Models\Market;
use App\Services\ParimatchScraper;
use App\Services\StoiximanScraper;
use ReflectionMethod;
use Tests\TestCase;

class ParimatchScraperTest extends TestCase
{
    public function test_event_ids_are_larger_than_stoiximan_ids(): void
    {
        $parimatch = $this->invokePrivateMethod(new ParimatchScraper, 'toBigIntId', ['event', '17318049']);
        $stoiximan = $this->invokePrivateMethod(new StoiximanScraper, 'toBigIntId', ['event', '17318049']);

        $this->assertGreaterThan($stoiximan, $parimatch);
        $this->assertGreaterThanOrEqual(9_000_000_000_000_000_000, $parimatch);
    }

    public function test_normalize_market_type_maps_parimatch_labels(): void
    {
        $scraper = new ParimatchScraper;

        $this->assertSame(
            Market::TYPE_MATCH_RESULT,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['Full-time result', 'Orgryte BK', 'BK Hacken'])
        );
        $this->assertSame(
            Market::TYPE_DOUBLE_CHANCE,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['Double chance', 'Orgryte BK', 'BK Hacken'])
        );
        $this->assertSame(
            Market::TYPE_TOTAL_ASIAN,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['Total', 'Orgryte BK', 'BK Hacken'])
        );
        $this->assertSame(
            Market::TYPE_HOME_TOTAL_ASIAN,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['ORGRYTE BK TOTAL', 'Orgryte BK', 'BK Hacken'])
        );
        $this->assertSame(
            Market::TYPE_AWAY_TOTAL_ASIAN,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['BK HACKEN TOTAL', 'Orgryte BK', 'BK Hacken'])
        );
        $this->assertSame(
            Market::TYPE_BTTS,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['Both teams to score', 'Orgryte BK', 'BK Hacken'])
        );
        $this->assertSame(
            Market::TYPE_HOME_TO_SCORE,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['ORGRYTE BK TO SCORE A GOAL', 'Orgryte BK', 'BK Hacken'])
        );
        $this->assertSame(
            Market::TYPE_AWAY_TO_SCORE,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['BK HACKEN TO SCORE A GOAL', 'Orgryte BK', 'BK Hacken'])
        );
        $this->assertSame(
            Market::TYPE_HANDICAP_ASIAN,
            $this->invokePrivateMethod($scraper, 'normalizeMarketType', ['Handicap', 'Orgryte BK', 'BK Hacken'])
        );
    }

    public function test_deduplicate_markets_for_event_keeps_distinct_market_types(): void
    {
        $markets = [
            [
                'external_id' => 'total',
                'type' => 'Total',
                'period' => Market::PERIOD_FULL_TIME,
                'selections' => [],
            ],
            [
                'external_id' => 'handicap',
                'type' => 'Handicap',
                'period' => Market::PERIOD_FULL_TIME,
                'selections' => [],
            ],
        ];

        $unique = $this->invokePrivateMethod(
            new ParimatchScraper,
            'deduplicateMarketsForEvent',
            [$markets, 'Orgryte BK', 'BK Hacken']
        );

        $this->assertCount(2, $unique);
    }

    /**
     * @param  array<int, mixed>  $args
     */
    private function invokePrivateMethod(object $object, string $method, array $args): mixed
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($object, ...$args);
    }
}
