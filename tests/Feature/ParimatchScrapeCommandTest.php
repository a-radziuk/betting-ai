<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Tournament;
use App\Services\ParimatchScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParimatchScrapeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_persists_events_from_fixture_payload(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Allsvenskan',
            'country' => 'Sweden',
            'parimatch_url' => 'https://parimatch.example.com/allsvenskan',
        ]);

        $payload = [
            'events' => [
                [
                    'external_id' => '17318049',
                    'url' => 'https://parimatch.example.com/events/orgryte-bk-hacken-17318049',
                    'home_team' => 'Orgryte BK',
                    'away_team' => 'BK Hacken',
                    'start_time' => '2026-07-12T15:00:00.000Z',
                    'markets' => [
                        [
                            'external_id' => 'full-time-result',
                            'type' => 'Full-time result',
                            'period' => Market::PERIOD_FULL_TIME,
                            'line' => null,
                            'selections' => [
                                ['external_id' => 'HOME', 'name' => 'HOME', 'odds' => 2.5, 'handicap' => null],
                                ['external_id' => 'DRAW', 'name' => 'DRAW', 'odds' => 3.4, 'handicap' => null],
                                ['external_id' => 'AWAY', 'name' => 'AWAY', 'odds' => 2.8, 'handicap' => null],
                            ],
                        ],
                        [
                            'external_id' => 'total',
                            'type' => 'Total',
                            'period' => Market::PERIOD_FULL_TIME,
                            'line' => null,
                            'selections' => [
                                ['external_id' => 'OVER_2.5', 'name' => 'OVER', 'odds' => 1.9, 'handicap' => 2.5],
                                ['external_id' => 'UNDER_2.5', 'name' => 'UNDER', 'odds' => 1.95, 'handicap' => 2.5],
                                ['external_id' => 'OVER_3', 'name' => 'OVER', 'odds' => 2.1, 'handicap' => 3],
                                ['external_id' => 'UNDER_3', 'name' => 'UNDER', 'odds' => 1.75, 'handicap' => 3],
                            ],
                        ],
                        [
                            'external_id' => 'btts',
                            'type' => 'Both teams to score',
                            'period' => Market::PERIOD_FULL_TIME,
                            'line' => null,
                            'selections' => [
                                ['external_id' => 'YES', 'name' => 'YES', 'odds' => 1.7, 'handicap' => null],
                                ['external_id' => 'NO', 'name' => 'NO', 'odds' => 2.1, 'handicap' => null],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->partialMock(ParimatchScraper::class, function ($mock) use ($payload): void {
            $mock->shouldReceive('fetchPayload')->once()->andReturn($payload);
        });

        $this->artisan('parimatch:scrape', ['tournamentId' => $tournament->id])
            ->assertSuccessful();

        $event = Event::query()->first();
        $this->assertNotNull($event);
        $this->assertSame(Event::SOURCE_PARIMATCH, $event->source);
        $this->assertGreaterThanOrEqual(9_000_000_000_000_000_000, $event->id);

        $marketTypes = Market::query()->where('event_id', $event->id)->pluck('type')->sort()->values()->all();
        $this->assertSame(
            [Market::TYPE_BTTS, Market::TYPE_MATCH_RESULT, Market::TYPE_TOTAL_ASIAN],
            $marketTypes
        );

        $totalMarket = Market::query()
            ->where('event_id', $event->id)
            ->where('type', Market::TYPE_TOTAL_ASIAN)
            ->first();

        $this->assertNotNull($totalMarket);
        $this->assertNull($totalMarket->line);
        $this->assertSame(4, $totalMarket->selections()->count());
        $this->assertSame(
            [2.5, 3.0],
            $totalMarket->selections()->pluck('value')->map(fn ($line) => (float) $line)->unique()->sort()->values()->all()
        );
        $this->assertTrue($totalMarket->selections()->whereNotNull('handicap')->doesntExist());
    }

    public function test_command_fails_when_tournament_has_no_parimatch_url(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'No URL League',
        ]);

        $this->artisan('parimatch:scrape', ['tournamentId' => $tournament->id])
            ->assertFailed();
    }
}
