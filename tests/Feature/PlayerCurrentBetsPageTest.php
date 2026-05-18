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
use Tests\TestCase;

class PlayerCurrentBetsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{user: User, eSoon: Event, eLater: Event}
     */
    private function seedPlayerWithPendingBets(): array
    {
        $user = User::factory()->create();

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

        return ['user' => $user, 'eSoon' => $eSoon, 'eLater' => $eLater];
    }

    public function test_lists_only_pending_bets_ordered_by_nearest_event(): void
    {
        ['user' => $user] = $this->seedPlayerWithPendingBets();

        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $html = $this->actingAs($viewer)
            ->get(route('players.current', ['user' => $user->id]))
            ->assertOk()
            ->getContent();

        $posSoon = strpos($html, 'Home — Away');
        $posLater = strpos($html, 'Away — Home');

        $this->assertNotFalse($posSoon);
        $this->assertNotFalse($posLater);
        $this->assertLessThan($posLater, $posSoon);
    }

    public function test_player_can_see_own_current_bets_without_see_tips_privilege(): void
    {
        ['user' => $user] = $this->seedPlayerWithPendingBets();

        $this->actingAs($user)
            ->get(route('players.current', ['user' => $user->id]))
            ->assertOk()
            ->assertSee('Home — Away', false)
            ->assertDontSee('Subscribe to see the tips', false);
    }

    public function test_shows_subscribe_message_when_viewer_lacks_see_tips_privilege(): void
    {
        ['user' => $user] = $this->seedPlayerWithPendingBets();
        $viewer = User::factory()->create();

        $html = $this->actingAs($viewer)
            ->get(route('players.current', ['user' => $user->id]))
            ->assertOk()
            ->assertSee('Subscribe to see the tips', false)
            ->getContent();

        $this->assertStringNotContainsString('Home — Away', $html);
        $this->assertStringNotContainsString('Away — Home', $html);
    }

    public function test_requires_auth(): void
    {
        $user = User::factory()->create();

        $this->get(route('players.current', ['user' => $user->id]))
            ->assertRedirect(route('login'));
    }

    public function test_shows_explanation_under_bet_row_when_present(): void
    {
        ['user' => $user, 'eSoon' => $eSoon] = $this->seedPlayerWithPendingBets();

        UserBet::query()
            ->where('user_id', $user->id)
            ->where('event_id', $eSoon->id)
            ->where('status', UserBet::STATUS_PENDING)
            ->update(['explanation' => 'Home side value based on recent form.']);

        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $this->actingAs($viewer)
            ->get(route('players.current', ['user' => $user->id]))
            ->assertOk()
            ->assertSee('Home side value based on recent form.', false)
            ->assertSee('player-current-bet-explanation', false);
    }

    public function test_hides_explanation_row_when_explanation_missing(): void
    {
        ['user' => $user] = $this->seedPlayerWithPendingBets();

        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $html = $this->actingAs($viewer)
            ->get(route('players.current', ['user' => $user->id]))
            ->assertOk()
            ->getContent();

        $this->assertSame(0, substr_count($html, 'class="player-current-bet-explanation"'));
    }
}
