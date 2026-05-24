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
use App\Services\UserBetDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBetDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_pending_bet_reverts_placement_wallet_changes(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update([
            'balance' => 100,
            'amount_in_play' => 0,
            'total_result' => 0,
        ]);

        [, $oddId] = $this->seedEventWithOdd(94001);
        $placeBet = app(PlaceBetService::class);
        $placeBet->placeBet($user->id, $oddId, '10');

        $bet = UserBet::query()->first();
        $this->assertNotNull($bet);

        $result = app(UserBetDeletionService::class)->deleteAndRevertWallet($bet);

        $this->assertTrue($result['ok']);
        $this->assertNull(UserBet::query()->find($bet->id));

        $wallet = UserWallet::query()->where('user_id', $user->id)->first();
        $this->assertSame('100.00', $wallet->balance);
        $this->assertSame('0.00', $wallet->amount_in_play);
        $this->assertSame('0.00', $wallet->total_result);
    }

    public function test_deleting_won_bet_reverts_settlement_wallet_changes(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update([
            'balance' => 100,
            'amount_in_play' => 10,
            'total_result' => 0,
        ]);

        $bet = UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $this->seedEventWithOdd(94002)[0],
            'odd_id' => 94005,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'status' => UserBet::STATUS_WON,
            'real_return' => 10,
            'resolved_order' => 1,
        ]);

        UserWallet::query()->where('user_id', $user->id)->update([
            'balance' => 110,
            'amount_in_play' => 0,
            'total_result' => 10,
        ]);

        app(UserBetDeletionService::class)->deleteAndRevertWallet($bet);

        $wallet = UserWallet::query()->where('user_id', $user->id)->first();
        $this->assertSame('100.00', $wallet->balance);
        $this->assertSame('0.00', $wallet->amount_in_play);
        $this->assertSame('0.00', $wallet->total_result);
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
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$eventId, $oddId];
    }
}
