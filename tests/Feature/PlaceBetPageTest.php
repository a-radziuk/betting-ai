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

class PlaceBetPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWhoCanPlaceBets(): User
    {
        return User::factory()->create([
            'priveleges' => User::PRIVELEGE_PLACE_BETS,
        ]);
    }

    private function seedOddChain(int $eventId, string $eventStatus): Odd
    {
        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => $eventStatus,
        ]);

        $market = Market::query()->create([
            'id' => $eventId * 100 + 1,
            'event_id' => $event->id,
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

        return Odd::query()->create([
            'id' => $eventId * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);
    }

    public function test_place_bet_page_requires_auth(): void
    {
        $odd = $this->seedOddChain(90101, Event::STATUS_SCHEDULED);

        $this->get(route('bets.place.show', ['odd' => $odd->id]))
            ->assertRedirect('/login');
    }

    public function test_place_bet_page_forbidden_without_place_bets_privilege(): void
    {
        $odd = $this->seedOddChain(90105, Event::STATUS_SCHEDULED);
        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $this->actingAs($user)
            ->get(route('bets.place.show', ['odd' => $odd->id]))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('bets.place.store', ['odd' => $odd->id]), ['sum' => 10])
            ->assertForbidden();
    }

    public function test_place_bet_page_rejects_non_scheduled_event(): void
    {
        $odd = $this->seedOddChain(90102, Event::STATUS_FINISHED);
        $user = $this->userWhoCanPlaceBets();

        $this->actingAs($user)
            ->get(route('bets.place.show', ['odd' => $odd->id]))
            ->assertStatus(400);
    }

    public function test_place_bet_post_validates_wallet_balance(): void
    {
        $odd = $this->seedOddChain(90103, Event::STATUS_SCHEDULED);
        $user = $this->userWhoCanPlaceBets();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 5]);

        $this->actingAs($user)
            ->post(route('bets.place.store', ['odd' => $odd->id]), ['sum' => 10])
            ->assertSessionHasErrors(['sum']);

        $this->assertSame(0, UserBet::query()->count());
        $this->assertSame('5.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_place_bet_post_places_bet_and_redirects_to_dashboard(): void
    {
        $odd = $this->seedOddChain(90104, Event::STATUS_SCHEDULED);
        $user = $this->userWhoCanPlaceBets();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 100]);

        $this->actingAs($user)
            ->post(route('bets.place.store', ['odd' => $odd->id]), ['sum' => 10])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(1, UserBet::query()->count());
        $this->assertSame('90.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }
}
