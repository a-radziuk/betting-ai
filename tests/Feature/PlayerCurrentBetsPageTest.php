<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerCurrentBetsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_only_pending_bets_ordered_by_nearest_event(): void
    {
        $user = User::factory()->create();
        $viewer = User::factory()->create();
        UserSubscription::query()->create([
            'subscriber_user_id' => $viewer->id,
            'player_user_id' => $user->id,
        ]);

        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        $eSoon = Event::query()->create([
            'id' => 70001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addHours(2),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $eLater = Event::query()->create([
            'id' => 70002,
            'home_team_id' => $away->id,
            'away_team_id' => $home->id,
            'start_time' => now()->addDays(2),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $mkSoon = Market::query()->create([
            'id' => 71001,
            'event_id' => $eSoon->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selSoon = Selection::query()->create([
            'id' => 72001,
            'market_id' => $mkSoon->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $oddSoon = Odd::query()->create([
            'id' => 73001,
            'selection_id' => $selSoon->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $mkLater = Market::query()->create([
            'id' => 71002,
            'event_id' => $eLater->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selLater = Selection::query()->create([
            'id' => 72002,
            'market_id' => $mkLater->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $oddLater = Odd::query()->create([
            'id' => 73002,
            'selection_id' => $selLater->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eLater->id,
            'odd_id' => $oddLater->id,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'real_return' => 0,
            'wallet_total_result' => 0,
            'status' => UserBet::STATUS_PENDING,
        ]);
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eSoon->id,
            'odd_id' => $oddSoon->id,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'real_return' => 0,
            'wallet_total_result' => 0,
            'status' => UserBet::STATUS_PENDING,
        ]);
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eSoon->id,
            'odd_id' => $oddSoon->id,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'real_return' => 10,
            'wallet_total_result' => 0,
            'status' => UserBet::STATUS_WON,
        ]);

        $res = $this->actingAs($viewer)->get(route('players.current', ['user' => $user->id]));
        $res->assertOk();

        $html = $res->getContent();
        $this->assertIsString($html);

        $posSoon = strpos($html, 'Home — Away');
        $posLater = strpos($html, 'Away — Home');

        $this->assertNotFalse($posSoon);
        $this->assertNotFalse($posLater);
        $this->assertLessThan($posLater, $posSoon);
    }

    public function test_forbids_access_when_not_subscribed(): void
    {
        $user = User::factory()->create();
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('players.current', ['user' => $user->id]))
            ->assertRedirect(route('players.subscribe.show', ['user' => $user->id]));
    }
}
