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
        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'standings' => [
                'rows' => [
                    ['position' => 1, 'team' => 'Alpha United', 'points' => 9],
                ],
            ],
        ]);
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
            'tournament_id' => $tournament->id,
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

        $this->assertSame('99001', $decoded['eventId']);
        $this->assertSame('H vs A', $decoded['eventName']);
        $this->assertSame('Premier League', $decoded['eventTournament']);
        $this->assertSame($startTime->toIso8601String(), $decoded['eventDateTime']);

        $this->assertArrayHasKey('standings', $decoded);
        $this->assertIsArray($decoded['standings']);
        $this->assertTrue(array_is_list($decoded['standings']));
        $this->assertCount(1, $decoded['standings']);
        $this->assertSame(1, $decoded['standings'][0]['position']);
        $this->assertSame('Alpha United', $decoded['standings'][0]['team']);
        $this->assertSame(9, $decoded['standings'][0]['points']);

        $this->assertArrayHasKey('odds', $decoded);
        $this->assertTrue(array_is_list($decoded['odds']));
        $this->assertCount(1, $decoded['odds']);

        $row = $decoded['odds'][0];
        $this->assertSame(Market::TYPE_MATCH_RESULT, $row['type']);
        $this->assertSame(Market::PERIOD_FULL_TIME, $row['period']);
        $this->assertSame('HOME', $row['selection']);
        $this->assertSame(2.5, $row['odds']);
    }

    public function test_no_markets_excludes_listed_types_case_insensitively(): void
    {
        $tournament = Tournament::query()->create(['name' => 'Div One']);
        $home = Team::query()->create([
            'name' => 'H2',
            'short_name' => 'H2',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A2',
            'short_name' => 'A2',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);

        $startTime = Carbon::parse('2026-06-16 19:00:00', 'UTC');

        Event::query()->create([
            'id' => 99200,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => $startTime,
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $mrMarket = Market::query()->create([
            'id' => 99201,
            'event_id' => 99200,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $mrSel = Selection::query()->create([
            'id' => 99202,
            'market_id' => $mrMarket->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 99203,
            'selection_id' => $mrSel->id,
            'odds' => 2.1,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $hcMarket = Market::query()->create([
            'id' => 99204,
            'event_id' => 99200,
            'type' => Market::TYPE_HANDICAP,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $hcSel = Selection::query()->create([
            'id' => 99205,
            'market_id' => $hcMarket->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => -0.25,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 99206,
            'selection_id' => $hcSel->id,
            'odds' => 1.95,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99200]);
        $this->assertSame(0, $exit);
        $full = json_decode(Artisan::output(), true);
        $this->assertArrayHasKey('standings', $full);
        $this->assertNull($full['standings']);
        $this->assertCount(2, $full['odds']);

        $exit = Artisan::call('event:export', [
            'eventId' => 99200,
            '--no-markets' => ' HANDICAP , correct_score ',
        ]);
        $this->assertSame(0, $exit);
        $filtered = json_decode(Artisan::output(), true);
        $this->assertCount(1, $filtered['odds']);
        $this->assertSame(Market::TYPE_MATCH_RESULT, $filtered['odds'][0]['type']);

        $exit = Artisan::call('event:export', [
            'eventId' => 99200,
            '--no-markets' => 'MATCH_RESULT,HANDICAP',
        ]);
        $this->assertSame(0, $exit);
        $emptyOdds = json_decode(Artisan::output(), true);
        $this->assertCount(0, $emptyOdds['odds']);
    }

    public function test_fails_when_event_missing(): void
    {
        $exit = Artisan::call('event:export', ['eventId' => 99999999]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Event not found', Artisan::output());
    }
}
