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

class PlaceBetCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedOddChain(): array
    {
        $home = Team::query()->create([
            'name' => 'Home FC',
            'short_name' => 'HOM',
            'league' => 'Test',
        ]);
        $away = Team::query()->create([
            'name' => 'Away FC',
            'short_name' => 'AWY',
            'league' => 'Test',
        ]);

        $event = Event::query()->create([
            'id' => 9001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 9101,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
        ]);

        $selection = Selection::query()->create([
            'id' => 9201,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => 9301,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => 0.4,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return ['odd_id' => $odd->id, 'event_id' => $event->id];
    }

    public function test_insufficient_balance_exits_with_failure(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 5]);
        ['odd_id' => $oddId] = $this->seedOddChain();

        $exit = Artisan::call('bet:place', [
            'odd_id' => $oddId,
            'user_id' => $user->id,
            'sum' => 10,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Insufficient', Artisan::output());
        $this->assertSame(0, UserBet::query()->count());
        $this->assertSame('5.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_sufficient_balance_places_bet_and_debits_wallet(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 100]);
        ['odd_id' => $oddId] = $this->seedOddChain();

        $exit = Artisan::call('bet:place', [
            'odd_id' => $oddId,
            'user_id' => $user->id,
            'sum' => 10,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Bet placed', Artisan::output());

        $bet = UserBet::query()->first();
        $this->assertNotNull($bet);
        $this->assertSame($user->id, $bet->user_id);
        $this->assertSame((string) $oddId, (string) $bet->odd_id);
        $this->assertSame('10.00', (string) $bet->stake);
        $this->assertSame('90.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }
}
