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
use Tests\TestCase;

class PlayersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_players_index_shows_last_non_pending_bets_as_form_icons(): void
    {
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $event = Event::query()->create([
            'id' => 91001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $market = Market::query()->create([
            'id' => 91010,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 91020,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => 91030,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $player = User::factory()->create(['name' => 'FormListPlayer']);
        UserWallet::query()->where('user_id', $player->id)->update(['total_result' => 100]);

        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => '5.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '10.00',
            'status' => UserBet::STATUS_PENDING,
        ]);
        $bWon = UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => '10.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '20.00',
            'status' => UserBet::STATUS_WON,
        ]);
        $bLost = UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => '8.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '16.00',
            'status' => UserBet::STATUS_LOST,
        ]);

        $html = $this->get(route('players.index'))
            ->assertOk()
            ->assertSee('FormListPlayer', false)
            ->getContent();

        $this->assertStringContainsString('form-icon--w', $html);
        $this->assertStringContainsString('form-icon--l', $html);
    }
}
