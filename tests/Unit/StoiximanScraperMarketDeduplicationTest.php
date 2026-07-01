<?php

namespace Tests\Unit;

use App\Models\Market;
use App\Services\StoiximanScraper;
use ReflectionMethod;
use Tests\TestCase;

class StoiximanScraperMarketDeduplicationTest extends TestCase
{
    public function test_unique_markets_collapses_aliases_with_same_normalized_type(): void
    {
        $markets = [
            [
                'external_id' => '111',
                'type' => 'Match Result',
                'period' => Market::PERIOD_FULL_TIME,
                'line' => null,
                'selections' => [],
            ],
            [
                'external_id' => '222',
                'type' => '1X2',
                'period' => Market::PERIOD_FULL_TIME,
                'line' => null,
                'selections' => [],
            ],
        ];

        $unique = $this->invokePrivateMethod(new StoiximanScraper, 'uniqueMarkets', [$markets]);

        $this->assertCount(1, $unique);
        $this->assertSame('111', $unique[0]['external_id']);
    }

    public function test_deduplicate_markets_for_event_keeps_distinct_lines(): void
    {
        $markets = [
            [
                'external_id' => '1',
                'type' => 'Over/Under Total Goals',
                'period' => Market::PERIOD_FULL_TIME,
                'line' => 2.5,
                'selections' => [],
            ],
            [
                'external_id' => '2',
                'type' => 'Over/Under Total Goals',
                'period' => Market::PERIOD_FULL_TIME,
                'line' => 3.5,
                'selections' => [],
            ],
        ];

        $unique = $this->invokePrivateMethod(
            new StoiximanScraper,
            'deduplicateMarketsForEvent',
            [$markets, 'Arsenal', 'Chelsea']
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
