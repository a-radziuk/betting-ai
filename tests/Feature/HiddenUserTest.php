<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HiddenUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_user_is_excluded_from_players_index(): void
    {
        $visible = $this->createPlayerWithResolvedBet('Visible Player', 100.0);
        $hidden = $this->createPlayerWithResolvedBet('Hidden Player', 200.0, hidden: true);

        $this->get(route('players.index'))
            ->assertOk()
            ->assertSee('Visible Player', false)
            ->assertDontSee('Hidden Player', false);

        $this->assertNotSame($visible->id, $hidden->id);
    }

    public function test_hidden_user_profile_returns_not_found(): void
    {
        $hidden = $this->createPlayerWithResolvedBet('Hidden Player', 50.0, hidden: true);

        $this->get(route('players.show', $hidden))->assertNotFound();
    }

    public function test_hidden_user_is_excluded_from_homepage_top_bettors(): void
    {
        $this->createPlayerWithResolvedBet('Visible Player', 10.0);
        $this->createPlayerWithResolvedBet('Hidden Player', 999.0, hidden: true);

        $this->get(url('/'))
            ->assertOk()
            ->assertSee('Visible Player', false)
            ->assertDontSee('Hidden Player', false);
    }

    public function test_hidden_user_is_excluded_from_event_tips(): void
    {
        $visible = $this->createPlayerWithResolvedBet('Visible Tipster', 10.0);
        $hidden = $this->createPlayerWithResolvedBet('Hidden Tipster', 20.0, hidden: true);

        $event = $this->createFinishedEvent(88001);
        $this->attachWonBet($visible, $event, 8800101);
        $this->attachWonBet($hidden, $event, 8800102);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Visible Tipster', false)
            ->assertDontSee('Hidden Tipster', false);
    }

    public function test_admin_can_still_manage_hidden_user(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $hidden = $this->createPlayerWithResolvedBet('Hidden Player', 10.0, hidden: true);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $hidden))
            ->assertOk()
            ->assertSee('Hidden Player', false)
            ->assertSee(__('Hidden from public site'), false);
    }

    public function test_admin_can_mark_user_as_hidden(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $player = $this->createPlayerWithResolvedBet('Soon Hidden', 10.0);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $player), [
                'name' => $player->name,
                'email' => $player->email,
                'password' => '',
                'password_confirmation' => '',
                'wallet_balance' => number_format((float) $player->wallet->balance, 2, '.', ''),
                'is_hidden' => '1',
            ])
            ->assertRedirect(route('admin.users'));

        $player->refresh();
        $this->assertTrue($player->is_hidden);

        $this->get(route('players.show', $player))->assertNotFound();
    }

    private function createPlayerWithResolvedBet(string $name, float $totalResult, bool $hidden = false): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'is_hidden' => $hidden,
        ]);
        $user->wallet->update(['total_result' => $totalResult]);

        $event = $this->createFinishedEvent(87000 + $user->id);
        $this->attachWonBet($user, $event, 8700000 + $user->id);

        return $user->fresh(['wallet']);
    }

    private function createFinishedEvent(int $eventId): Event
    {
        $home = Team::query()->firstOrCreate(
            ['short_name' => 'H', 'league' => 'T'],
            ['name' => 'Home Team'],
        );
        $away = Team::query()->firstOrCreate(
            ['short_name' => 'A', 'league' => 'T'],
            ['name' => 'Away Team'],
        );

        return Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);
    }

    private function attachWonBet(User $user, Event $event, int $suffix): void
    {
        $market = Market::query()->create([
            'id' => $suffix,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $suffix + 1,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $suffix + 2,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => '10.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '20.00',
            'status' => UserBet::STATUS_WON,
            'wallet_total_result' => (string) $user->wallet->total_result,
            'resolved_order' => 1,
        ]);
    }
}
