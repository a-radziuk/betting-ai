<?php

namespace Tests\Unit;

use App\Services\FifaRankingsService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FifaRankingsServiceTest extends TestCase
{
    #[Test]
    public function test_parses_rankings_payload(): void
    {
        $service = new FifaRankingsService;

        $rankings = $service->parseRankingsPayload([
            'Results' => [
                [
                    'TeamName' => [
                        ['Locale' => 'en-GB', 'Description' => 'Argentina'],
                    ],
                    'Rank' => 1,
                    'DecimalTotalPoints' => 1889.06,
                ],
                [
                    'TeamName' => [
                        ['Locale' => 'en-GB', 'Description' => 'France'],
                    ],
                    'Rank' => 2,
                    'TotalPoints' => 1887,
                ],
            ],
        ]);

        $this->assertSame([
            ['name' => 'Argentina', 'rank' => 1, 'points' => 1889.06],
            ['name' => 'France', 'rank' => 2, 'points' => 1887.0],
        ], $rankings);
    }

    #[Test]
    public function test_throws_when_results_missing(): void
    {
        $service = new FifaRankingsService;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing "Results"');

        $service->parseRankingsPayload([]);
    }
}
