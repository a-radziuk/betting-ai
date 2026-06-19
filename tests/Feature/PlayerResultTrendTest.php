<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Services\PlayerShowDataService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerResultTrendTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_subscribe_page(): void
    {
        $player = User::factory()->create();

        $this->get(route('players.result-trend', $player))
            ->assertRedirect(route('subscribe'));
    }

    public function test_user_without_see_tips_is_redirected_to_subscribe_page(): void
    {
        $player = User::factory()->create();
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('players.result-trend', $player))
            ->assertRedirect(route('subscribe'));
    }

    public function test_user_with_see_tips_sees_full_history_chart(): void
    {
        $player = User::factory()->create();
        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $this->seedResolvedBets($player, 31);

        $this->actingAs($viewer)
            ->get(route('players.result-trend', $player))
            ->assertOk()
            ->assertSee('Full cumulative result trend', false)
            ->assertSee('All 31 resolved bets', false);
    }

    public function test_full_chart_includes_all_resolved_bets(): void
    {
        $player = User::factory()->create();
        $this->seedResolvedBets($player, 31);

        $chart = app(PlayerShowDataService::class)->buildFullResultChart($player);

        $this->assertCount(31, $chart->values);
        $this->assertTrue($chart->points[0]['isOrigin']);
        $this->assertSame(0.0, $chart->points[0]['value']);
        $this->assertSame(310.0, $chart->latest);
        $this->assertSame('2 Jan 2026', $chart->points[1]['date']);
        $this->assertNotEmpty($chart->axisDateLabels());
    }

    public function test_result_trend_page_shows_chart_dates(): void
    {
        $player = User::factory()->create();
        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);
        $this->seedResolvedBets($player, 5);

        $this->actingAs($viewer)
            ->get(route('players.result-trend', $player))
            ->assertOk()
            ->assertSee('user-results-chart-axis', false)
            ->assertSee('2 Jan', false)
            ->assertSee('6 Jan', false);
    }

    public function test_player_page_shows_full_trend_link_even_without_see_tips(): void
    {
        $player = User::factory()->create();
        $viewer = User::factory()->create();
        $this->seedResolvedBets($player, 2);

        $this->actingAs($viewer)
            ->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('View full trend', false)
            ->assertSee(route('players.result-trend', $player), false);
    }

    public function test_guest_sees_full_trend_link_on_player_page(): void
    {
        $player = User::factory()->create();
        $this->seedResolvedBets($player, 2);

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('View full trend', false)
            ->assertSee(route('players.result-trend', $player), false);
    }

    public function test_player_page_shows_full_trend_link_without_resolved_bets(): void
    {
        $player = User::factory()->create();

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('View full trend', false)
            ->assertSee(route('players.result-trend', $player), false);
    }

    private function seedResolvedBets(User $player, int $count): void
    {
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);

        for ($order = 1; $order <= $count; $order++) {
            $eventId = 94000 + $order;
            Event::query()->create([
                'id' => $eventId,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'start_time' => Carbon::parse('2026-01-01 12:00:00')->addDays($order),
                'status' => Event::STATUS_FINISHED,
            ]);

            $market = Market::query()->create([
                'id' => $eventId * 100 + 1,
                'event_id' => $eventId,
                'type' => Market::TYPE_MATCH_RESULT,
                'period' => Market::PERIOD_FULL_TIME,
                'line' => null,
                'status' => Market::STATUS_OPEN,
                'is_supported_market' => true,
            ]);

            $selection = Selection::query()->create([
                'id' => $eventId * 100 + 2,
                'market_id' => $market->id,
                'name' => Selection::NAME_HOME,
                'participant_id' => null,
                'handicap' => null,
                'created_at' => now(),
            ]);

            $odd = Odd::query()->create([
                'id' => $eventId * 100 + 3,
                'selection_id' => $selection->id,
                'odds' => 2,
                'probability' => null,
                'is_active' => true,
                'created_at' => now(),
            ]);

            $resolvedAt = Carbon::parse('2026-01-01 12:00:00')->addDays($order);

            $bet = UserBet::query()->create([
                'user_id' => $player->id,
                'event_id' => $eventId,
                'odd_id' => $odd->id,
                'stake' => '10.00',
                'odds_at_bet' => '2.0000',
                'potential_return' => '20.00',
                'status' => UserBet::STATUS_WON,
                'wallet_total_result' => (string) ($order * 10),
                'resolved_order' => $order,
            ]);
            $bet->forceFill([
                'created_at' => $resolvedAt,
                'updated_at' => $resolvedAt,
            ])->saveQuietly();
        }
    }
}
