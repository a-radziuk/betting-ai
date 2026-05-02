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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BetEventResultCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedMatchResultBet(int $eventId, string $selectionName, float $odds = 2.0): User
    {
        $home = Team::query()->create(['name' => 'Alpha', 'short_name' => 'ALP', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Beta', 'short_name' => 'BET', 'league' => 'T']);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
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
            'name' => $selectionName,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $eventId * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => $odds,
            'probability' => 0.5,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 990]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => $odds,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        return $user;
    }

    public function test_settles_win_and_credits_wallet(): void
    {
        $user = $this->seedMatchResultBet(88001, 'HOME', 2.0);

        $exit = Artisan::call('bet:event:result', [
            'event_id' => 88001,
            'result' => '2:0',
            'additional_data' => '{"corners":"10:12"}',
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame('2:0', Event::query()->find(88001)->score);
        $this->assertSame(Event::STATUS_FINISHED, Event::query()->find(88001)->status);
        $this->assertSame('won', UserBet::query()->first()->status);
        $this->assertSame('1010.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_settles_loss_without_wallet_credit(): void
    {
        $user = $this->seedMatchResultBet(88002, 'HOME', 2.0);

        $exit = Artisan::call('bet:event:result', [
            'event_id' => 88002,
            'result' => '0:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame('lost', UserBet::query()->first()->status);
        $this->assertSame('990.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_draw_no_bet_refunds_on_draw(): void
    {
        $home = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'B', 'short_name' => 'B', 'league' => 'T']);
        $eid = 88003;

        Event::query()->create([
            'id' => $eid,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $eid * 100 + 1,
            'event_id' => $eid,
            'type' => Market::TYPE_DRAW_NO_BET,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eid * 100 + 2,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $eid * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => 1.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 990]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eid,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 1.5,
            'potential_return' => 15,
            'status' => UserBet::STATUS_PENDING,
        ]);

        Artisan::call('bet:event:result', [
            'event_id' => $eid,
            'result' => '1:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame('void', UserBet::query()->first()->status);
        $this->assertSame('1000.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }
}
