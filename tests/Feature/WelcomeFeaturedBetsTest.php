<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Support\HomepageFeaturedBets;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeFeaturedBetsTest extends TestCase
{
    use RefreshDatabase;

    private function seedOddForEvent(
        int $eventId,
        string $homeName,
        string $awayName,
        ?Carbon $startTime = null,
    ): Odd {
        $home = Team::query()->create(['name' => $homeName, 'short_name' => substr($homeName, 0, 3), 'league' => 'T']);
        $away = Team::query()->create(['name' => $awayName, 'short_name' => substr($awayName, 0, 3), 'league' => 'T']);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => $startTime ?? now()->addDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);

        $market = Market::query()->create([
            'id' => $eventId + 100,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId + 200,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        return Odd::query()->create([
            'id' => $eventId + 300,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);
    }

    private function createResolvedBet(
        User $user,
        Odd $odd,
        int $eventId,
        int $resolvedOrder,
        string $status,
        float $realReturn,
        float $stake = 10,
    ): void {
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => $stake,
            'odds_at_bet' => 2,
            'potential_return' => $stake * 2,
            'status' => $status,
            'resolved_order' => $resolvedOrder,
            'real_return' => $realReturn,
            'wallet_total_result' => $realReturn,
        ]);
    }

    public function test_featured_pool_uses_ten_bets_from_most_recent_events_by_start_time(): void
    {
        $user = User::factory()->create();

        $oldOdd = $this->seedOddForEvent(
            87001,
            'OldHome',
            'OldAway',
            Carbon::parse('2026-01-01 15:00:00', config('app.timezone')),
        );
        $this->createResolvedBet($user, $oldOdd, 87001, 999, UserBet::STATUS_WON, 500);

        for ($i = 0; $i < 11; $i++) {
            $eventId = 87100 + $i;
            $odd = $this->seedOddForEvent(
                $eventId,
                "Home{$i}",
                "Away{$i}",
                Carbon::parse('2026-06-01 12:00:00', config('app.timezone'))->addDays($i),
            );
            $this->createResolvedBet($user, $odd, $eventId, $i, UserBet::STATUS_WON, 10 + $i);
        }

        $pool = HomepageFeaturedBets::latestResolvedQuery()->limit(10)->get();

        $this->assertCount(10, $pool);
        $this->assertFalse($pool->contains(fn (UserBet $bet) => $bet->event_id === 87001));
        $this->assertSame(
            range(87101, 87110),
            $pool->pluck('event_id')->sort()->values()->all(),
        );
    }

    public function test_pick_one_per_user_from_recent_events_in_event_order_not_profit(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $third = User::factory()->create();
        $odd = $this->seedOddForEvent(87001, 'Alpha', 'Beta');

        $this->createResolvedBet($first, $odd, 87001, 1, UserBet::STATUS_WON, 50);
        $this->createResolvedBet($first, $odd, 87001, 2, UserBet::STATUS_WON, 45);
        $this->createResolvedBet($second, $odd, 87001, 3, UserBet::STATUS_LOST, -10);
        $this->createResolvedBet($third, $odd, 87001, 4, UserBet::STATUS_WON, 35);

        $pool = HomepageFeaturedBets::latestResolvedQuery()->get();
        $featured = HomepageFeaturedBets::pickOnePerUserFromRecentEvents($pool);

        $this->assertCount(3, $featured);
        $this->assertSame([35.0, -10.0, 45.0], $featured->map(fn (UserBet $bet) => (float) $bet->real_return)->all());
        $this->assertSame([4, 3, 2], $featured->pluck('resolved_order')->all());
        $this->assertCount(3, $featured->pluck('user_id')->unique());
    }

    public function test_home_shows_top_three_featured_bets_under_top_bettors(): void
    {
        $odd = $this->seedOddForEvent(87101, 'FeaturedHome', 'FeaturedAway');
        $winner = User::factory()->create(['name' => 'FeaturedWinner']);
        $other = User::factory()->create(['name' => 'FeaturedOther']);
        $third = User::factory()->create(['name' => 'FeaturedThird']);

        $this->createResolvedBet($winner, $odd, 87101, 1, UserBet::STATUS_WON, 999);
        for ($order = 2; $order <= 9; $order++) {
            $user = match ($order % 3) {
                0 => $other,
                1 => $winner,
                default => $third,
            };
            $this->createResolvedBet($user, $odd, 87101, $order, UserBet::STATUS_WON, 5 + $order);
        }
        $this->createResolvedBet($other, $odd, 87101, 10, UserBet::STATUS_WON, 80, 20);
        $this->createResolvedBet($winner, $odd, 87101, 11, UserBet::STATUS_WON, 60);
        $this->createResolvedBet($other, $odd, 87101, 12, UserBet::STATUS_LOST, -10);
        $this->createResolvedBet($third, $odd, 87101, 13, UserBet::STATUS_WON, 40);

        $html = $this->get('/')
            ->assertOk()
            ->assertSee('See all players', false)
            ->assertSee(route('players.index'), false)
            ->assertSee('Latest bet results', false)
            ->assertSee('FeaturedHome', false)
            ->assertSee('FeaturedAway', false)
            ->assertSee('FeaturedWinner', false)
            ->assertSee('FeaturedOther', false)
            ->assertSee('FeaturedThird', false)
            ->assertSee('-10.00 EUR', false)
            ->assertSee('+60.00 EUR', false)
            ->assertSee('+40.00 EUR', false)
            ->assertDontSee('+999.00 EUR', false)
            ->assertDontSee('+80.00 EUR', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/Top bettors.+Latest bet results/s',
            $html,
        );
    }

    public function test_home_hides_featured_bets_when_no_resolved_bets(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Latest bet results', false);
    }

    public function test_home_excludes_pending_bets_from_featured_pool(): void
    {
        $odd = $this->seedOddForEvent(87201, 'PendingHome', 'PendingAway');
        $user = User::factory()->create(['name' => 'PendingUser']);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => 87201,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Latest bet results', false);
    }
}
