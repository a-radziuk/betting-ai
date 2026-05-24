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
use App\Services\PlaceBetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserBetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_user_bets(): void
    {
        $this->get(route('admin.user-bets'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_admin_user_bets(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get(route('admin.user-bets'))
            ->assertForbidden();
    }

    public function test_superadmin_sees_active_user_bets(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $bettor = User::factory()->create(['name' => 'Active Bettor']);
        $home = Team::query()->create(['name' => 'Home FC', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away FC', 'short_name' => 'AWY', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 93001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 93010,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => 93011,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => 93012,
            'selection_id' => $selection->id,
            'odds' => 2.1,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $bettor->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 25,
            'odds_at_bet' => 2.1,
            'potential_return' => 52.5,
            'status' => UserBet::STATUS_PENDING,
            'explanation' => 'Home edge at home.',
        ]);

        UserBet::query()->create([
            'user_id' => $bettor->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2.1,
            'potential_return' => 21,
            'status' => UserBet::STATUS_WON,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.user-bets'))
            ->assertOk()
            ->assertSee('User Bets', false)
            ->assertSee('Active Bettor', false)
            ->assertSee('Home FC', false)
            ->assertSee('HOME (MATCH_RESULT)', false)
            ->assertSee('Home edge at home.', false)
            ->assertSee('25.00', false);

        $html = $this->actingAs($admin)
            ->get(route('admin.user-bets'))
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Active Bettor'));
    }

    public function test_superadmin_can_delete_pending_bet_and_revert_wallet(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $bettor = User::factory()->create(['name' => 'Delete Me Bettor']);
        UserWallet::query()->where('user_id', $bettor->id)->update([
            'balance' => 200,
            'amount_in_play' => 0,
        ]);

        [, $oddId] = $this->seedEventWithOdd(93002);
        app(PlaceBetService::class)->placeBet($bettor->id, $oddId, '30');

        $bet = UserBet::query()->first();
        $this->assertNotNull($bet);

        $this->actingAs($admin)
            ->delete(route('admin.user-bets.destroy', $bet))
            ->assertRedirect(route('admin.user-bets'))
            ->assertSessionHas('status');

        $this->assertNull(UserBet::query()->find($bet->id));
        $this->assertSame('200.00', UserWallet::query()->where('user_id', $bettor->id)->value('balance'));
        $this->assertSame('0.00', UserWallet::query()->where('user_id', $bettor->id)->value('amount_in_play'));
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function seedEventWithOdd(int $eventId): array
    {
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $eventId + 1,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId + 2,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $oddId = $eventId + 3;
        Odd::query()->create([
            'id' => $oddId,
            'selection_id' => $selection->id,
            'odds' => 2.1,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$eventId, $oddId];
    }
}
