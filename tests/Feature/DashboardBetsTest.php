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
use Tests\TestCase;

class DashboardBetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_user_bets_newest_first(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 1000]);

        $home = Team::query()->create(['name' => 'Alpha', 'short_name' => 'ALP', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Beta', 'short_name' => 'BET', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 7001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 7101,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
        ]);

        $selection = Selection::query()->create([
            'id' => 7201,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => 7301,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => 0.5,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 33.33,
            'odds_at_bet' => 2,
            'potential_return' => 66.66,
            'status' => UserBet::STATUS_PENDING,
            'created_at' => now()->subHour(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 44.44,
            'odds_at_bet' => 2,
            'potential_return' => 88.88,
            'status' => UserBet::STATUS_PENDING,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Your bets', false);
        $response->assertSeeInOrder(['44.44', '33.33']);
        $response->assertSee('Match Result', false);
        $response->assertDontSee(Market::TYPE_MATCH_RESULT, false);
    }

    public function test_dashboard_shows_score_when_event_not_scheduled(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 1000]);

        $home = Team::query()->create(['name' => 'ShowSc', 'short_name' => 'S1', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'OppSc', 'short_name' => 'S2', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 7002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '2:1',
        ]);

        $market = Market::query()->create([
            'id' => 7102,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
        ]);

        $selection = Selection::query()->create([
            'id' => 7202,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => 7302,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('2:1', false);
    }
}
