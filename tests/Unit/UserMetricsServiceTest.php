<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserMetric;
use App\Services\UserMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_all_positive_metrics_for_eligible_player(): void
    {
        $user = User::factory()->create([
            'is_metrics_available' => true,
        ]);
        $user->wallet->update(['total_result' => 150.0]);

        $this->seedWonBets($user, 30, stake: 10.0, odds: 2.0);

        $metrics = app(UserMetricsService::class)->buildMetricsForUser($user->fresh('wallet'));

        $types = array_column($metrics, 'type');

        $this->assertContains(UserMetric::TYPE_TOTAL_RESULT_POSITIVE, $types);
        $this->assertContains(UserMetric::TYPE_LAST_10_POSITIVE, $types);
        $this->assertContains(UserMetric::TYPE_LAST_20_POSITIVE, $types);
        $this->assertContains(UserMetric::TYPE_LAST_30_POSITIVE, $types);

        $totalMetric = collect($metrics)->firstWhere('type', UserMetric::TYPE_TOTAL_RESULT_POSITIVE);
        $this->assertSame(150.0, $totalMetric['amount']);
        $this->assertSame(['won' => 30, 'lost' => 0, 'drawn' => 0], $totalMetric['bets_stats']);

        $last10Metric = collect($metrics)->firstWhere('type', UserMetric::TYPE_LAST_10_POSITIVE);
        $this->assertSame(100.0, $last10Metric['amount']);
        $this->assertSame(['won' => 10, 'lost' => 0, 'drawn' => 0], $last10Metric['bets_stats']);
    }

    public function test_records_bets_stats_for_total_and_recent_positive_metrics(): void
    {
        $user = User::factory()->create([
            'is_metrics_available' => true,
        ]);
        $user->wallet->update(['total_result' => 120.0]);

        for ($order = 1; $order <= 12; $order++) {
            $status = match (true) {
                $order <= 5 => UserBet::STATUS_WON,
                $order <= 8 => UserBet::STATUS_LOST,
                $order === 9 => UserBet::STATUS_VOID,
                default => UserBet::STATUS_WON,
            };

            $this->seedBet($user, $order, $status, stake: 10.0, odds: 2.0);
        }

        $metrics = app(UserMetricsService::class)->buildMetricsForUser($user->fresh('wallet'));

        $totalMetric = collect($metrics)->firstWhere('type', UserMetric::TYPE_TOTAL_RESULT_POSITIVE);
        $last10Metric = collect($metrics)->firstWhere('type', UserMetric::TYPE_LAST_10_POSITIVE);

        $this->assertSame(['won' => 8, 'lost' => 3, 'drawn' => 1], $totalMetric['bets_stats']);
        $this->assertSame(['won' => 6, 'lost' => 3, 'drawn' => 1], $last10Metric['bets_stats']);
    }

    public function test_winning_streak_metric_has_no_bets_stats(): void
    {
        $user = User::factory()->create([
            'is_metrics_available' => true,
        ]);

        $this->seedWonBets($user, 6, stake: 10.0, odds: 2.0);

        $streakMetric = collect(app(UserMetricsService::class)->buildMetricsForUser($user))
            ->firstWhere('type', UserMetric::TYPE_WINNING_STREAK);

        $this->assertNull($streakMetric['bets_stats']);
    }

    public function test_records_winning_streak_when_more_than_five_wins_in_a_row(): void
    {
        $user = User::factory()->create([
            'is_metrics_available' => true,
        ]);

        $this->seedWonBets($user, 6, stake: 10.0, odds: 2.0);

        $metrics = app(UserMetricsService::class)->buildMetricsForUser($user);

        $streakMetric = collect($metrics)->firstWhere('type', UserMetric::TYPE_WINNING_STREAK);

        $this->assertNotNull($streakMetric);
        $this->assertSame(6, $streakMetric['length']);
        $this->assertSame(60.0, $streakMetric['amount']);
    }

    public function test_generate_deletes_metrics_older_than_one_day_and_creates_new_ones(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $user = User::factory()->create([
            'is_metrics_available' => true,
        ]);
        $user->wallet->update(['total_result' => 25.0]);
        $this->seedBet($user, 1, UserBet::STATUS_WON, stake: 10.0, odds: 2.0);

        $oldMetric = UserMetric::query()->create([
            'user_id' => $user->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 1.00,
        ]);
        $oldMetric->forceFill([
            'created_at' => '2026-06-08 12:00:00',
            'updated_at' => '2026-06-08 12:00:00',
        ])->saveQuietly();

        User::factory()->create([
            'is_metrics_available' => false,
        ])->wallet->update(['total_result' => 999.0]);

        $result = app(UserMetricsService::class)->generate();

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(1, $result['users_processed']);
        $this->assertSame(1, $result['metrics_created']);

        $this->assertDatabaseMissing('user_metrics', [
            'user_id' => $user->id,
            'amount' => 1.00,
        ]);

        $this->assertDatabaseHas('user_metrics', [
            'user_id' => $user->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 25.00,
            'length' => null,
        ]);

        $storedMetric = UserMetric::query()
            ->where('user_id', $user->id)
            ->where('type', UserMetric::TYPE_TOTAL_RESULT_POSITIVE)
            ->first();

        $this->assertSame(['won' => 1, 'lost' => 0, 'drawn' => 0], $storedMetric->bets_stats);

        $this->assertSame(
            '2026-06-10 12:00:00',
            $user->fresh()->metrics_updated_at?->toDateTimeString(),
        );

        Carbon::setTestNow();
    }

    public function test_generate_skips_users_updated_within_last_day(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $dueUser = User::factory()->create([
            'is_metrics_available' => true,
            'metrics_updated_at' => '2026-06-08 12:00:00',
        ]);
        $dueUser->wallet->update(['total_result' => 25.0]);

        $recentUser = User::factory()->create([
            'is_metrics_available' => true,
            'metrics_updated_at' => '2026-06-10 06:00:00',
        ]);
        $recentUser->wallet->update(['total_result' => 50.0]);

        $result = app(UserMetricsService::class)->generate();

        $this->assertSame(1, $result['users_processed']);
        $this->assertSame(1, $result['metrics_created']);

        $this->assertDatabaseHas('user_metrics', [
            'user_id' => $dueUser->id,
            'amount' => 25.00,
        ]);

        $this->assertDatabaseMissing('user_metrics', [
            'user_id' => $recentUser->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_generate_processes_users_with_null_metrics_updated_at(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $user = User::factory()->create([
            'is_metrics_available' => true,
            'metrics_updated_at' => null,
        ]);
        $user->wallet->update(['total_result' => 15.0]);

        $result = app(UserMetricsService::class)->generate();

        $this->assertSame(1, $result['users_processed']);
        $this->assertSame('2026-06-10 12:00:00', $user->fresh()->metrics_updated_at?->toDateTimeString());

        Carbon::setTestNow();
    }

    private function seedWonBets(User $user, int $count, float $stake, float $odds): void
    {
        for ($order = 1; $order <= $count; $order++) {
            $this->seedBet($user, $order, UserBet::STATUS_WON, $stake, $odds);
        }
    }

    private function seedBet(
        User $user,
        int $resolvedOrder,
        string $status,
        float $stake,
        float $odds,
    ): void {
        $eventId = 96000 + ($user->id * 100) + $resolvedOrder;
        $home = Team::query()->firstOrCreate(
            ['short_name' => 'HM', 'league' => 'T'],
            ['name' => 'Home Metrics'],
        );
        $away = Team::query()->firstOrCreate(
            ['short_name' => 'AW', 'league' => 'T'],
            ['name' => 'Away Metrics'],
        );

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subDays($resolvedOrder),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);

        $market = Market::query()->create([
            'id' => $eventId * 10 + 1,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId * 10 + 2,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $eventId * 10 + 3,
            'selection_id' => $selection->id,
            'odds' => $odds,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => number_format($stake, 2, '.', ''),
            'odds_at_bet' => number_format($odds, 4, '.', ''),
            'potential_return' => number_format($stake * $odds, 2, '.', ''),
            'status' => $status,
            'wallet_total_result' => number_format($resolvedOrder * 10, 2, '.', ''),
            'resolved_order' => $resolvedOrder,
        ]);
    }
}
