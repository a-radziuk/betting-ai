<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EventExportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_flat_json_rows(): void
    {
        $tournament = Tournament::query()->create(['name' => 'Premier League']);
        $home = Team::query()->create([
            'name' => 'H',
            'short_name' => 'H',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A',
            'short_name' => 'A',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);

        $startTime = Carbon::parse('2026-06-15 18:30:00', 'UTC');

        Event::query()->create([
            'id' => 99001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => $startTime,
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 99002,
            'event_id' => 99001,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => 99003,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        Odd::query()->create([
            'id' => 99004,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99001]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);

        $this->assertSame('H vs A', $decoded['eventName']);
        $this->assertSame('Premier League', $decoded['eventTournament']);
        $this->assertSame($startTime->toIso8601String(), $decoded['eventDateTime']);

        $this->assertArrayHasKey('odds', $decoded);
        $this->assertTrue(array_is_list($decoded['odds']));
        $this->assertCount(1, $decoded['odds']);

        $row = $decoded['odds'][0];
        $this->assertSame(Market::TYPE_MATCH_RESULT, $row['type']);
        $this->assertSame(Market::PERIOD_FULL_TIME, $row['period']);
        $this->assertSame('HOME', $row['selection']);
        $this->assertSame(2.5, $row['odds']);
    }

    public function test_fails_when_event_missing(): void
    {
        $exit = Artisan::call('event:export', ['eventId' => 99999999]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Event not found', Artisan::output());
    }
}
