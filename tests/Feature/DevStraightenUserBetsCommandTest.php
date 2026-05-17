<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DevStraightenUserBetsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_straightens_wallet_total_result_as_running_sum_of_real_return(): void
    {
        $user = User::factory()->create();
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);

        foreach ([
            [1, '10.00', '5.00', 2],
            [2, '25.00', '-3.00', 1],
            [3, '99.00', '8.00', 3],
        ] as [$resolvedOrder, $wrongWalletTotal, $realReturn, $eventId]) {
            Event::query()->create([
                'id' => 93000 + $eventId,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'start_time' => now()->addDay(),
                'status' => Event::STATUS_FINISHED,
            ]);

            UserBet::query()->create([
                'user_id' => $user->id,
                'event_id' => 93000 + $eventId,
                'odd_id' => 93000 + $eventId,
                'stake' => 10,
                'odds_at_bet' => 2,
                'potential_return' => 20,
                'status' => UserBet::STATUS_WON,
                'real_return' => $realReturn,
                'wallet_total_result' => $wrongWalletTotal,
                'resolved_order' => $resolvedOrder,
            ]);
        }

        $exit = Artisan::call('dev:straighten-user-bets', ['userId' => $user->id]);
        $this->assertSame(0, $exit);

        $straightened = UserBet::query()
            ->where('user_id', $user->id)
            ->orderBy('resolved_order')
            ->orderBy('id')
            ->pluck('wallet_total_result', 'resolved_order')
            ->all();

        $this->assertSame([
            1 => '0.00',
            2 => '5.00',
            3 => '2.00',
        ], array_map(
            static fn ($value) => number_format((float) $value, 2, '.', ''),
            $straightened,
        ));
    }

    public function test_fails_when_user_not_found(): void
    {
        $exit = Artisan::call('dev:straighten-user-bets', ['userId' => 999999]);
        $this->assertSame(1, $exit);
    }
}
