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

class WelcomeTopBettorsTest extends TestCase
{
    use RefreshDatabase;

    private function seedOddForBets(): Odd
    {
        $home = Team::query()->create(['name' => 'Alpha', 'short_name' => 'ALP', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Beta', 'short_name' => 'BET', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 88001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 88010,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => 88020,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        return Odd::query()->create([
            'id' => 88030,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);
    }

    private function placeBet(User $user, Odd $odd, int $eventId, float $stake = 10): void
    {
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => $stake,
            'odds_at_bet' => 2,
            'potential_return' => $stake * 2,
            'status' => UserBet::STATUS_PENDING,
        ]);
    }

    public function test_home_shows_top_three_bettors_by_wallet_total_result(): void
    {
        $odd = $this->seedOddForBets();
        $eventId = 88001;

        $first = User::factory()->create(['name' => 'LeaderBoardFirst']);
        $second = User::factory()->create(['name' => 'LeaderBoardSecond']);
        $third = User::factory()->create(['name' => 'LeaderBoardThird']);
        $fourth = User::factory()->create(['name' => 'LeaderBoardFourth']);

        UserWallet::query()->where('user_id', $first->id)->update(['total_result' => 150.5]);
        UserWallet::query()->where('user_id', $second->id)->update(['total_result' => 400.25]);
        UserWallet::query()->where('user_id', $third->id)->update(['total_result' => 275]);
        UserWallet::query()->where('user_id', $fourth->id)->update(['total_result' => 12]);

        $this->placeBet($first, $odd, $eventId);
        $this->placeBet($second, $odd, $eventId, 10);
        $this->placeBet($second, $odd, $eventId, 20);
        $this->placeBet($third, $odd, $eventId);
        $this->placeBet($fourth, $odd, $eventId);

        $second->forceFill(['avatar' => 'https://example.test/avatar.png'])->saveQuietly();

        $secondBets = UserBet::query()->where('user_id', $second->id)->orderBy('id')->get();
        $this->assertCount(2, $secondBets);
        $secondBets[0]->update(['status' => UserBet::STATUS_WON]);
        $secondBets[1]->update(['status' => UserBet::STATUS_LOST]);

        $html = $this->get('/')
            ->assertOk()
            ->assertSee('Top bettors', false)
            ->assertDontSee('Total result', false)
            ->assertSeeInOrder([
                'LeaderBoardSecond',
                '2 bets',
                '30.00 EUR',
                '+400.25 EUR',
                'LeaderBoardThird',
                '1 bet',
                '10.00 EUR',
                '+275.00 EUR',
                'LeaderBoardFirst',
                '1 bet',
                '10.00 EUR',
                '+150.50 EUR',
            ], false)
            ->getContent();

        $this->assertStringContainsString('welcome-bettor-card-link', $html);
        $this->assertStringContainsString(route('players.show', $second), $html);
        $this->assertStringContainsString('https://example.test/avatar.png', $html);
        $this->assertStringContainsString('welcome-bettor-card-avatar-placeholder', $html);
        $this->assertStringContainsString('form-icon--w', $html);
        $this->assertStringContainsString('form-icon--l', $html);
        $this->assertStringContainsString('form-icon--muted', $html);

        $this->get('/')
            ->assertDontSee('LeaderBoardFourth', false);
    }

    public function test_home_excludes_users_with_no_bets_even_with_high_wallet(): void
    {
        $odd = $this->seedOddForBets();
        $eventId = 88001;

        $withBet = User::factory()->create(['name' => 'HasBetUser']);
        $noBet = User::factory()->create(['name' => 'NoBetHighWallet']);

        UserWallet::query()->where('user_id', $withBet->id)->update(['total_result' => 10]);
        UserWallet::query()->where('user_id', $noBet->id)->update(['total_result' => 50000]);

        $this->placeBet($withBet, $odd, $eventId);

        $this->get('/')
            ->assertOk()
            ->assertSee('HasBetUser', false)
            ->assertDontSee('NoBetHighWallet', false);
    }
}
