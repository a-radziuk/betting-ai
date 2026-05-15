<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerStatsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolved_bets_sorted_by_event_start_time_desc(): void
    {
        $tz = config('app.timezone');
        $player = User::factory()->create();

        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);

        $eventEarly = Event::query()->create([
            'id' => 92001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-03-01 12:00:00', $tz),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);
        $eventLate = Event::query()->create([
            'id' => 92002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-06-01 12:00:00', $tz),
            'status' => Event::STATUS_FINISHED,
            'score' => '2-2',
        ]);

        $market = Market::query()->create([
            'id' => 92010,
            'event_id' => $eventEarly->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 92020,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $oddEarly = Odd::query()->create([
            'id' => 92030,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $marketLate = Market::query()->create([
            'id' => 92011,
            'event_id' => $eventLate->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selectionLate = Selection::query()->create([
            'id' => 92021,
            'market_id' => $marketLate->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $oddLate = Odd::query()->create([
            'id' => 92031,
            'selection_id' => $selectionLate->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $eventEarly->id,
            'odd_id' => $oddEarly->id,
            'stake' => '10.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '20.00',
            'status' => UserBet::STATUS_WON,
        ]);
        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $eventLate->id,
            'odd_id' => $oddLate->id,
            'stake' => '5.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '10.00',
            'status' => UserBet::STATUS_LOST,
        ]);

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->assertDontSee('<th>Resolved</th>', false)
            ->getContent();

        $posLate = strpos($html, '2026-06-01 12:00');
        $posEarly = strpos($html, '2026-03-01 12:00');
        $this->assertNotFalse($posLate);
        $this->assertNotFalse($posEarly);
        $this->assertLessThan($posEarly, $posLate);
    }
}
