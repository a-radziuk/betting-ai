<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BetsRandomResolveAllCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_pending_bets_for_event(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 1000]);

        $home = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'B', 'short_name' => 'B', 'league' => 'T']);
        $eid = 88010;
        Event::query()->create([
            'id' => $eid,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $market = Market::query()->create([
            'id' => 88011,
            'event_id' => $eid,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 88012,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => 88013,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eid,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $exit = Artisan::call('bets:random-resolve-all');

        $this->assertSame(0, $exit);
        $bet = UserBet::query()->first();
        $this->assertNotSame(UserBet::STATUS_PENDING, $bet->status);
        $this->assertNotNull(Event::query()->find($eid)->score);
        $this->assertSame(Event::STATUS_FINISHED, Event::query()->find($eid)->status);
    }
}
