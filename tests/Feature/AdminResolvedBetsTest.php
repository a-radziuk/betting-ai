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

class AdminResolvedBetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_resolved_bets(): void
    {
        $this->get(route('admin.resolved-bets'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_admin_resolved_bets(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get(route('admin.resolved-bets'))
            ->assertForbidden();
    }

    public function test_editor_cannot_access_admin_resolved_bets(): void
    {
        $editor = User::factory()->create([
            'is_superadmin' => false,
            'priveleges' => User::PRIVELEGE_EDITOR,
        ]);

        $this->actingAs($editor)
            ->get(route('admin.resolved-bets'))
            ->assertForbidden();
    }

    public function test_superadmin_sees_resolved_bets_but_not_pending_ones(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $bettor = User::factory()->create(['name' => 'Resolved Bettor']);
        [$event, $odd] = $this->seedEventWithOdd(94001, 'Arsenal FC', 'Chelsea FC');

        UserBet::query()->create([
            'user_id' => $bettor->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 25,
            'odds_at_bet' => 2.1,
            'potential_return' => 52.5,
            'real_return' => 52.5,
            'wallet_total_result' => 27.5,
            'status' => UserBet::STATUS_WON,
            'resolved_order' => 2,
            'explanation' => 'Strong home form.',
        ]);

        $resolvedBetId = UserBet::query()->where('status', UserBet::STATUS_WON)->value('id');

        UserBet::query()->create([
            'user_id' => $bettor->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2.1,
            'potential_return' => 21,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.resolved-bets'))
            ->assertOk()
            ->assertSee('Resolved Bets', false)
            ->assertSee('Resolved Bettor', false)
            ->assertSee((string) $resolvedBetId, false)
            ->assertSee('Arsenal FC', false)
            ->assertSee('Chelsea FC', false)
            ->assertSee('Strong home form.', false)
            ->assertSee('27.50', false)
            ->assertDontSee('Delete', false);

        $html = $this->actingAs($admin)
            ->get(route('admin.resolved-bets'))
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Resolved Bettor'));
    }

    public function test_superadmin_can_search_resolved_bets_by_event_name(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $bettor = User::factory()->create(['name' => 'Search Bettor']);

        [$arsenalEvent, $arsenalOdd] = $this->seedEventWithOdd(94010, 'Arsenal FC', 'Chelsea FC', 94010);
        [$cityEvent, $cityOdd] = $this->seedEventWithOdd(94020, 'Manchester City', 'Liverpool FC', 94020);

        UserBet::query()->create([
            'user_id' => $bettor->id,
            'event_id' => $arsenalEvent->id,
            'odd_id' => $arsenalOdd->id,
            'stake' => 10,
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'status' => UserBet::STATUS_WON,
            'resolved_order' => 1,
        ]);
        UserBet::query()->create([
            'user_id' => $bettor->id,
            'event_id' => $cityEvent->id,
            'odd_id' => $cityOdd->id,
            'stake' => 15,
            'odds_at_bet' => 1.8,
            'potential_return' => 27,
            'status' => UserBet::STATUS_LOST,
            'resolved_order' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.resolved-bets', ['search' => 'Arsenal']))
            ->assertOk()
            ->assertSee('Arsenal FC', false)
            ->assertDontSee('Manchester City', false);

        $this->actingAs($admin)
            ->get(route('admin.resolved-bets', ['search' => 'Liverpool']))
            ->assertOk()
            ->assertSee('Liverpool FC', false)
            ->assertDontSee('Arsenal FC', false);
    }

    /**
     * @return array{0: Event, 1: Odd}
     */
    private function seedEventWithOdd(int $eventId, string $homeName, string $awayName, ?int $marketId = null): array
    {
        $marketId ??= $eventId + 1;

        $home = Team::query()->create(['name' => $homeName, 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => $awayName, 'short_name' => 'A', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '2-1',
        ]);

        $market = Market::query()->create([
            'id' => $marketId,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $marketId + 1,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $marketId + 2,
            'selection_id' => $selection->id,
            'odds' => 2.1,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$event, $odd];
    }
}
