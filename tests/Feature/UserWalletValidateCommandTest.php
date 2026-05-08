<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserWalletValidateCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedEvent(int $eventId): void
    {
        $home = Team::query()->create(['name' => 'H'.$eventId, 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A'.$eventId, 'short_name' => 'A', 'league' => 'T']);
        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
    }

    public function test_success_when_wallet_matches_bet_aggregates(): void
    {
        $user = User::factory()->create();
        $this->seedEvent(1);
        $this->seedEvent(2);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => 1,
            'odd_id' => 1,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'real_return' => 0,
            'wallet_total_result' => 0,
            'status' => UserBet::STATUS_PENDING,
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => 2,
            'odd_id' => 2,
            'stake' => 5,
            'odds_at_bet' => 2,
            'potential_return' => 10,
            'real_return' => 3.5,
            'wallet_total_result' => 0,
            'status' => UserBet::STATUS_WON,
        ]);

        UserWallet::query()->where('user_id', $user->id)->update([
            'amount_in_play' => 10,
            'total_result' => 3.5,
        ]);

        $exit = Artisan::call('user:wallet-validate', ['userId' => $user->id]);

        $this->assertSame(0, $exit);
    }

    public function test_failure_when_wallet_does_not_match(): void
    {
        $user = User::factory()->create();
        $this->seedEvent(1);
        $this->seedEvent(2);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => 1,
            'odd_id' => 1,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'real_return' => 0,
            'wallet_total_result' => 0,
            'status' => UserBet::STATUS_PENDING,
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => 2,
            'odd_id' => 2,
            'stake' => 5,
            'odds_at_bet' => 2,
            'potential_return' => 10,
            'real_return' => 3.5,
            'wallet_total_result' => 0,
            'status' => UserBet::STATUS_WON,
        ]);

        UserWallet::query()->where('user_id', $user->id)->update([
            'amount_in_play' => 999,
            'total_result' => 0,
        ]);

        $exit = Artisan::call('user:wallet-validate', ['userId' => $user->id]);

        $this->assertSame(1, $exit);
    }
}
