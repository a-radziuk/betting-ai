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
use App\Support\PromocodeGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_user_bets_newest_first(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_PLACE_BETS,
        ]);
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 1000]);

        $home = Team::query()->create(['name' => 'Alpha', 'short_name' => 'ALP', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Beta', 'short_name' => 'BET', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 7001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 7101,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
        ]);

        $selection = Selection::query()->create([
            'id' => 7201,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => 7301,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => 0.5,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 33.33,
            'odds_at_bet' => 2,
            'potential_return' => 66.66,
            'status' => UserBet::STATUS_PENDING,
            'created_at' => now()->subHour(),
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 44.44,
            'odds_at_bet' => 2,
            'potential_return' => 88.88,
            'status' => UserBet::STATUS_PENDING,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Your bets', false);
        $response->assertSeeInOrder(['44.44', '33.33']);
        $response->assertSee('Home', false);
        $response->assertDontSee(Selection::NAME_HOME, false);
        $response->assertSee('Match Result', false);
        $response->assertDontSee(Market::TYPE_MATCH_RESULT, false);
    }

    public function test_dashboard_shows_score_when_event_not_scheduled(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_PLACE_BETS,
        ]);
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 1000]);

        $home = Team::query()->create(['name' => 'ShowSc', 'short_name' => 'S1', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'OppSc', 'short_name' => 'S2', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 7002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '2:1',
        ]);

        $market = Market::query()->create([
            'id' => 7102,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
        ]);

        $selection = Selection::query()->create([
            'id' => 7202,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => 7302,
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
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('2:1', false);
    }

    public function test_dashboard_hides_wallet_and_bets_without_place_bets_privilege(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 500]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Wallet', false)
            ->assertDontSee('Your bets', false)
            ->assertDontSee('500.00', false)
            ->assertSee('Browse upcoming events', false);
    }

    public function test_dashboard_shows_subscription_expiration_when_tips_access_is_active(): void
    {
        $expiresAt = now()->addMonth();
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => $expiresAt,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Subscription', false)
            ->assertSee('active tips subscription', false)
            ->assertSee('Expires on', false)
            ->assertSee($expiresAt->timezone(config('app.timezone'))->translatedFormat('j M Y, H:i'), false);
    }

    public function test_dashboard_hides_subscription_when_tips_access_expired(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('active tips subscription', false)
            ->assertSee('name="code"', false)
            ->assertSee(__('Apply promocode'), false);
    }

    public function test_dashboard_hides_promocode_form_when_tips_access_is_active(): void
    {
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
            'see_tips_expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('active tips subscription', false)
            ->assertDontSee('name="code"', false);
    }

    public function test_user_can_redeem_promocode_from_dashboard(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $user = User::factory()->create();
        $promocode = PromocodeGenerator::generateUnique(2);

        $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('subscribe.promocode'), [
                'code' => $promocode->code,
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertTrue($user->fresh()->hasActiveSeeTipsAccess());

        Carbon::setTestNow();
    }
}
