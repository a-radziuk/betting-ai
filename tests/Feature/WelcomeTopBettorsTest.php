<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserMetric;
use App\Models\UserWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeTopBettorsTest extends TestCase
{
    use RefreshDatabase;

    private function seedOddForBets(): Odd
    {
        $home = Team::query()->create(['name' => 'Alpha', 'short_name' => 'ALP', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Beta', 'short_name' => 'BET', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 88001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 88010,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => 88020,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        return Odd::query()->create([
            'id' => 88030,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);
    }

    private function placeBet(User $user, Odd $odd, int $eventId, float $stake = 10): void
    {
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => $stake,
            'odds_at_bet' => 2,
            'potential_return' => $stake * 2,
            'status' => UserBet::STATUS_PENDING,
        ]);
    }

    public function test_home_shows_top_three_bettors_by_wallet_total_result(): void
    {
        $odd = $this->seedOddForBets();
        $eventId = 88001;

        $first = User::factory()->create(['name' => 'LeaderBoardFirst']);
        $second = User::factory()->create(['name' => 'LeaderBoardSecond']);
        $third = User::factory()->create(['name' => 'LeaderBoardThird']);
        $fourth = User::factory()->create(['name' => 'LeaderBoardFourth']);

        UserWallet::query()->where('user_id', $first->id)->update(['total_result' => 150.5]);
        UserWallet::query()->where('user_id', $second->id)->update(['total_result' => 400.25]);
        UserWallet::query()->where('user_id', $third->id)->update(['total_result' => 275]);
        UserWallet::query()->where('user_id', $fourth->id)->update(['total_result' => 12]);

        $this->placeBet($first, $odd, $eventId);
        $this->placeBet($second, $odd, $eventId, 10);
        $this->placeBet($second, $odd, $eventId, 20);
        $this->placeBet($third, $odd, $eventId);
        $this->placeBet($fourth, $odd, $eventId);

        UserBet::query()->where('user_id', $first->id)->update(['status' => UserBet::STATUS_WON]);
        UserBet::query()->where('user_id', $third->id)->update(['status' => UserBet::STATUS_LOST]);

        $second->forceFill(['avatar' => 'https://example.test/avatar.png'])->saveQuietly();

        $secondBets = UserBet::query()->where('user_id', $second->id)->orderBy('id')->get();
        $this->assertCount(2, $secondBets);
        $secondBets[0]->update(['status' => UserBet::STATUS_WON]);
        $secondBets[1]->update(['status' => UserBet::STATUS_LOST]);

        $html = $this->get('/')
            ->assertOk()
            ->assertSee('Top bettors', false)
            ->assertDontSee('Total result', false)
            ->assertSeeInOrder([
                'LeaderBoardSecond',
                '2 bets',
                '30.00 EUR',
                '+400.25 EUR',
                'LeaderBoardThird',
                '1 bet',
                '10.00 EUR',
                '+275.00 EUR',
                'LeaderBoardFirst',
                '1 bet',
                '10.00 EUR',
                '+150.50 EUR',
            ], false)
            ->getContent();

        $this->assertStringContainsString('welcome-bettor-card-link', $html);
        $this->assertStringContainsString(route('players.show', $second), $html);
        $this->assertStringContainsString('https://example.test/avatar.png', $html);
        $this->assertStringContainsString('welcome-bettor-card-avatar-placeholder', $html);
        $this->assertStringContainsString('form-icon--w', $html);
        $this->assertStringContainsString('form-icon--l', $html);
        $this->assertStringContainsString('form-icon--muted', $html);

        $this->get('/')
            ->assertDontSee('LeaderBoardFourth', false);
    }

    public function test_home_excludes_users_with_only_pending_bets_even_with_high_wallet(): void
    {
        $odd = $this->seedOddForBets();
        $eventId = 88001;

        $withResolved = User::factory()->create(['name' => 'ResolvedBettor']);
        $pendingOnly = User::factory()->create(['name' => 'PendingOnlyBettor']);

        UserWallet::query()->where('user_id', $withResolved->id)->update(['total_result' => 10]);
        UserWallet::query()->where('user_id', $pendingOnly->id)->update(['total_result' => 50000]);

        $this->placeBet($withResolved, $odd, $eventId);
        UserBet::query()->where('user_id', $withResolved->id)->update(['status' => UserBet::STATUS_WON]);

        $this->placeBet($pendingOnly, $odd, $eventId);

        $this->get('/')
            ->assertOk()
            ->assertSee('ResolvedBettor', false)
            ->assertDontSee('PendingOnlyBettor', false);
    }

    public function test_home_excludes_users_with_no_bets_even_with_high_wallet(): void
    {
        $odd = $this->seedOddForBets();
        $eventId = 88001;

        $withBet = User::factory()->create(['name' => 'HasBetUser']);
        $noBet = User::factory()->create(['name' => 'NoBetHighWallet']);

        UserWallet::query()->where('user_id', $withBet->id)->update(['total_result' => 10]);
        UserWallet::query()->where('user_id', $noBet->id)->update(['total_result' => 50000]);

        $this->placeBet($withBet, $odd, $eventId);
        UserBet::query()->where('user_id', $withBet->id)->update(['status' => UserBet::STATUS_WON]);

        $this->get('/')
            ->assertOk()
            ->assertSee('HasBetUser', false)
            ->assertDontSee('NoBetHighWallet', false);
    }

    public function test_home_shows_top_bettors_from_metrics_when_at_least_three_users_have_metrics(): void
    {
        $odd = $this->seedOddForBets();
        $eventId = 88001;

        $metricLeader = User::factory()->create(['name' => 'MetricLeader']);
        $metricSecond = User::factory()->create(['name' => 'MetricSecond']);
        $metricThird = User::factory()->create(['name' => 'MetricThird']);
        $walletLeader = User::factory()->create(['name' => 'WalletLeaderOnly']);

        foreach ([$metricLeader, $metricSecond, $metricThird, $walletLeader] as $user) {
            UserWallet::query()->where('user_id', $user->id)->update(['total_result' => 10.0]);
            $this->placeBet($user, $odd, $eventId);
            UserBet::query()->where('user_id', $user->id)->update(['status' => UserBet::STATUS_WON]);
        }

        UserWallet::query()->where('user_id', $walletLeader->id)->update(['total_result' => 999.0]);

        UserMetric::query()->create([
            'user_id' => $metricLeader->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 500.00,
            'bets_stats' => ['won' => 12, 'lost' => 3, 'drawn' => 1],
        ]);
        UserMetric::query()->create([
            'user_id' => $metricSecond->id,
            'type' => UserMetric::TYPE_LAST_10_POSITIVE,
            'amount' => 300.00,
            'bets_stats' => ['won' => 8, 'lost' => 2, 'drawn' => 0],
        ]);
        UserMetric::query()->create([
            'user_id' => $metricThird->id,
            'type' => UserMetric::TYPE_LAST_20_POSITIVE,
            'amount' => 200.00,
        ]);
        UserMetric::query()->create([
            'user_id' => $walletLeader->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 50.00,
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Top players, ranked by performance metrics.', false)
            ->assertSeeInOrder([
                'MetricLeader',
                '+500.00 EUR',
                'Total result',
                '12',
                '3',
                '1',
                'MetricSecond',
                '+300.00 EUR',
                'Last 10 bets',
                '8',
                '2',
                '0',
                'MetricThird',
                '+200.00 EUR',
                'Last 20 bets',
            ], false)
            ->assertDontSee('+999.00 EUR', false);

        $this->assertStringContainsString('form-icon--w', $response->getContent());
        $this->assertStringContainsString('form-icon--l', $response->getContent());
        $this->assertStringContainsString('form-icon--d', $response->getContent());
    }

    public function test_home_hero_shows_best_user_metric(): void
    {
        $odd = $this->seedOddForBets();
        $eventId = 88001;

        $leader = User::factory()->create(['name' => 'HeroLeader']);
        $runnerUp = User::factory()->create(['name' => 'HeroRunnerUp', 'is_hidden' => true]);

        foreach ([$leader, $runnerUp] as $user) {
            UserWallet::query()->where('user_id', $user->id)->update(['total_result' => 10.0]);
            $this->placeBet($user, $odd, $eventId);
            UserBet::query()->where('user_id', $user->id)->update(['status' => UserBet::STATUS_WON]);
        }

        UserMetric::query()->create([
            'user_id' => $leader->id,
            'type' => UserMetric::TYPE_LAST_10_POSITIVE,
            'amount' => 420.00,
        ]);
        UserMetric::query()->create([
            'user_id' => $runnerUp->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 150.00,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('home-hero-banner-featured', false)
            ->assertSee('HeroLeader', false)
            ->assertSee('Last 10 bets', false)
            ->assertSee('+420.00 EUR', false)
            ->assertDontSee('HeroRunnerUp', false);
    }

    public function test_home_falls_back_to_wallet_ranking_when_fewer_than_three_users_have_metrics(): void
    {
        $odd = $this->seedOddForBets();
        $eventId = 88001;

        $first = User::factory()->create(['name' => 'WalletFirst']);
        $second = User::factory()->create(['name' => 'WalletSecond']);
        $third = User::factory()->create(['name' => 'WalletThird']);

        UserWallet::query()->where('user_id', $first->id)->update(['total_result' => 100.0]);
        UserWallet::query()->where('user_id', $second->id)->update(['total_result' => 300.0]);
        UserWallet::query()->where('user_id', $third->id)->update(['total_result' => 200.0]);

        foreach ([$first, $second, $third] as $user) {
            $this->placeBet($user, $odd, $eventId);
            UserBet::query()->where('user_id', $user->id)->update(['status' => UserBet::STATUS_WON]);
        }

        UserMetric::query()->create([
            'user_id' => $first->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 500.00,
        ]);
        UserMetric::query()->create([
            'user_id' => $second->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 400.00,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Top players, ranked by lifetime wallet result.', false)
            ->assertSeeInOrder([
                'WalletSecond',
                '+300.00 EUR',
                'WalletThird',
                '+200.00 EUR',
                'WalletFirst',
                '+100.00 EUR',
            ], false);
    }
}
