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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResolveEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_resolve_event_page(): void
    {
        $this->get(route('admin.resolve-event'))
            ->assertRedirect(route('login'));
    }

    public function test_non_superadmin_cannot_access_resolve_event_page(): void
    {
        $user = User::factory()->create(['is_superadmin' => false]);

        $this->actingAs($user)
            ->get(route('admin.resolve-event'))
            ->assertForbidden();
    }

    public function test_lists_unresolved_events_started_more_than_two_hours_ago(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19 15:00:00', config('app.timezone')));

        $admin = User::factory()->create(['is_superadmin' => true]);
        $home = Team::query()->create(['name' => 'Home FC', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away FC', 'short_name' => 'AWY', 'league' => 'T']);

        $ready = Event::query()->create([
            'id' => 97001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subHours(3),
            'status' => Event::STATUS_LIVE,
        ]);

        Event::query()->create([
            'id' => 97002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subHour(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        Event::query()->create([
            'id' => 97003,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subHours(5),
            'status' => Event::STATUS_FINISHED,
            'score' => '2-1',
        ]);

        Event::query()->create([
            'id' => 97004,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addHour(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.resolve-event'))
            ->assertOk()
            ->assertSee('Home FC', false)
            ->assertSee('Away FC', false)
            ->assertSee('Resolve', false)
            ->assertSee(route('admin.resolve-event.show', $ready), false)
            ->assertDontSee('97002', false)
            ->assertDontSee('97003', false)
            ->assertDontSee('97004', false);

        Carbon::setTestNow();
    }

    public function test_shows_empty_state_when_no_events_ready(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.resolve-event'))
            ->assertOk()
            ->assertSee('No events ready to resolve', false);
    }

    public function test_resolve_page_shows_score_form(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $event = $this->createResolvableEvent(97101);

        $this->actingAs($admin)
            ->get(route('admin.resolve-event.show', $event))
            ->assertOk()
            ->assertSee('Final score', false)
            ->assertSee('Submit', false);
    }

    public function test_already_resolved_event_redirects_from_resolve_page(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $event = $this->createResolvableEvent(97102, Event::STATUS_FINISHED, '1-0');

        $this->actingAs($admin)
            ->get(route('admin.resolve-event.show', $event))
            ->assertRedirect(route('admin.resolve-event'))
            ->assertSessionHas('status');
    }

    public function test_superadmin_can_submit_score_and_settle_event(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $event = $this->createResolvableEvent(97103);
        $this->seedHomeWinBet($event->id);

        $this->actingAs($admin)
            ->post(route('admin.resolve-event.store', $event), ['score' => '2:0'])
            ->assertRedirect(route('admin.resolve-event'))
            ->assertSessionHas('status');

        $event->refresh();
        $this->assertSame(Event::STATUS_FINISHED, $event->status);
        $this->assertSame('2:0', $event->score);
        $this->assertSame(UserBet::STATUS_WON, UserBet::query()->where('event_id', $event->id)->value('status'));
    }

    public function test_invalid_score_format_returns_validation_error(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $event = $this->createResolvableEvent(97104);

        $response = $this->actingAs($admin)
            ->from(route('admin.resolve-event.show', $event))
            ->post(route('admin.resolve-event.store', $event), ['score' => 'invalid']);

        $response->assertRedirect(route('admin.resolve-event.show', $event));
        $response->assertSessionHas('errors');

        $this->assertSame(Event::STATUS_SCHEDULED, $event->fresh()->status);
    }

    private function createResolvableEvent(
        int $id,
        string $status = Event::STATUS_SCHEDULED,
        ?string $score = null,
    ): Event {
        $home = Team::query()->create(['name' => 'Home FC', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away FC', 'short_name' => 'AWY', 'league' => 'T']);

        return Event::query()->create([
            'id' => $id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subHours(3),
            'status' => $status,
            'score' => $score,
        ]);
    }

    private function seedHomeWinBet(int $eventId): User
    {
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
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => $eventId * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => 2.0,
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
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        return $user;
    }
}
