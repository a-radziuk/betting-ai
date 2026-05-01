<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ClearBetsDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_clears_events_markets_selections_and_odds_data(): void
    {
        $home = Team::query()->create([
            'name' => 'Clear Home',
            'short_name' => 'CLH',
            'league' => 'Test',
        ]);
        $away = Team::query()->create([
            'name' => 'Clear Away',
            'short_name' => 'CLA',
            'league' => 'Test',
        ]);

        $event = Event::query()->create([
            'id' => 5001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $market = Market::query()->create([
            'id' => 5002,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 5003,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => 5004,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => 0.4,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $user = User::factory()->create();
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2.5,
            'potential_return' => 25,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $exitCode = Artisan::call('bets:clear');

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, Event::query()->count());
        $this->assertSame(0, Market::query()->count());
        $this->assertSame(0, Selection::query()->count());
        $this->assertSame(0, Odd::query()->count());
        $this->assertSame(0, UserBet::query()->count());
    }
}
