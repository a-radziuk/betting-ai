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

class ResultsImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_results_and_settles_pending_bets(): void
    {
        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update([
            'balance' => 990,
            'amount_in_play' => 10,
            'total_result' => 0,
        ]);

        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 91001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_SCHEDULED,
            'score' => null,
        ]);

        $market = Market::query()->create([
            'id' => 91011,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => 91021,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => 91031,
            'selection_id' => $selection->id,
            'odds' => 2.0,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'real_return' => 0,
            'wallet_total_result' => 0,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $path = sys_get_temp_dir().'/results-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            ['eventId' => 91001, 'result' => '1:0'],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('results:import', ['filepath' => $path]);
            $this->assertSame(0, $exit);

            $event->refresh();
            $this->assertSame('1:0', $event->score);
            $this->assertSame(Event::STATUS_FINISHED, $event->status);

            $bet = UserBet::query()->where('event_id', $event->id)->first();
            $this->assertNotNull($bet);
            $this->assertSame(UserBet::STATUS_WON, $bet->status);

            $this->assertSame('1010.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
            $this->assertSame('0.00', UserWallet::query()->where('user_id', $user->id)->value('amount_in_play'));
        } finally {
            @unlink($path);
        }
    }

    public function test_skips_event_when_already_finished(): void
    {
        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        Event::query()->create([
            'id' => 91002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '2:2',
        ]);

        $path = sys_get_temp_dir().'/results-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            ['eventId' => 91002, 'result' => '1:0'],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('results:import', ['filepath' => $path]);
            $this->assertSame(0, $exit);
            $this->assertStringContainsString('already finished', Artisan::output());

            $event = Event::query()->find(91002);
            $this->assertNotNull($event);
            $this->assertSame('2:2', $event->score);
        } finally {
            @unlink($path);
        }
    }
}

