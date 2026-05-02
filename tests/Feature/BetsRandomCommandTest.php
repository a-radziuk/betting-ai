<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BetsRandomCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_random_bets_from_supported_markets(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 1000]);

        $home = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'B', 'short_name' => 'B', 'league' => 'T']);
        $event = Event::query()->create([
            'id' => 99001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $market = Market::query()->create([
            'id' => 99002,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 99003,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 99004,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $exit = Artisan::call('bets:random', [
            'userId' => $user->id,
            '--num-of-bets' => 3,
            '--sum' => 10,
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(3, \App\Models\UserBet::query()->where('user_id', $user->id)->count());
        $this->assertSame('970.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }
}
