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
        $this->assertSame(3, UserBet::query()->where('user_id', $user->id)->count());
        $this->assertSame('970.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_places_bets_only_for_given_event_when_event_option_set(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 1000]);

        $home = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'B', 'short_name' => 'B', 'league' => 'T']);

        $eventTarget = Event::query()->create([
            'id' => 99101,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $eventOther = Event::query()->create([
            'id' => 99102,
            'home_team_id' => $away->id,
            'away_team_id' => $home->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        foreach ([$eventTarget, $eventOther] as $event) {
            $market = Market::query()->create([
                'id' => $event->id * 100 + 2,
                'event_id' => $event->id,
                'type' => Market::TYPE_MATCH_RESULT,
                'period' => Market::PERIOD_FULL_TIME,
                'line' => null,
                'status' => Market::STATUS_OPEN,
                'is_supported_market' => true,
            ]);
            Selection::query()->create([
                'id' => $event->id * 100 + 3,
                'market_id' => $market->id,
                'name' => 'HOME',
                'participant_id' => null,
                'handicap' => null,
                'created_at' => now(),
            ]);
            Odd::query()->create([
                'id' => $event->id * 100 + 4,
                'selection_id' => $event->id * 100 + 3,
                'odds' => 2,
                'probability' => null,
                'is_active' => true,
                'created_at' => now(),
            ]);
        }

        $exit = Artisan::call('bets:random', [
            'userId' => $user->id,
            '--num-of-bets' => 5,
            '--sum' => 10,
            '--event' => (string) $eventTarget->id,
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(5, UserBet::query()->where('user_id', $user->id)->count());
        $this->assertSame(5, UserBet::query()->where('user_id', $user->id)->where('event_id', $eventTarget->id)->count());
    }

    public function test_fails_when_event_option_unknown(): void
    {
        $user = User::factory()->create();

        $exit = Artisan::call('bets:random', [
            'userId' => $user->id,
            '--event' => '99999999',
        ]);

        $this->assertSame(1, $exit);
        $this->assertSame(0, UserBet::query()->where('user_id', $user->id)->count());
    }

    public function test_fails_when_event_option_not_scheduled(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 1000]);

        $home = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'B', 'short_name' => 'B', 'league' => 'T']);
        $event = Event::query()->create([
            'id' => 99201,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subHour(),
            'status' => Event::STATUS_FINISHED,
        ]);
        $market = Market::query()->create([
            'id' => 99202,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        Selection::query()->create([
            'id' => 99203,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 99204,
            'selection_id' => 99203,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $exit = Artisan::call('bets:random', [
            'userId' => $user->id,
            '--num-of-bets' => 1,
            '--event' => (string) $event->id,
        ]);

        $this->assertSame(1, $exit);
        $this->assertSame(0, UserBet::query()->where('user_id', $user->id)->count());
    }
}
