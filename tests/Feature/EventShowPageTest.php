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

class EventShowPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{event: Event, odd: Odd}
     */
    private function seedEventWithOdd(int $eventId): array
    {
        $home = Team::query()->create(['name' => 'Event Home', 'short_name' => 'EH', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Event Away', 'short_name' => 'EA', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
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

        $odd = Odd::query()->create([
            'id' => $eventId * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => 2.15,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return ['event' => $event, 'odd' => $odd];
    }

    public function test_event_page_shows_user_bets_before_markets(): void
    {
        ['event' => $event, 'odd' => $odd] = $this->seedEventWithOdd(92001);

        $player = User::factory()->create(['name' => 'Tipster Alpha']);
        UserWallet::query()->where('user_id', $player->id)->update(['total_result' => 42.5]);

        ['event' => $pastEvent, 'odd' => $pastOdd] = $this->seedEventWithOdd(92011);

        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $pastEvent->id,
            'odd_id' => $pastOdd->id,
            'stake' => 10,
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'status' => UserBet::STATUS_WON,
        ]);
        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $pastEvent->id,
            'odd_id' => $pastOdd->id,
            'stake' => 12,
            'odds_at_bet' => 2.0,
            'potential_return' => 24,
            'status' => UserBet::STATUS_LOST,
        ]);

        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 25,
            'odds_at_bet' => 2.15,
            'potential_return' => 53.75,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Player tips', false)
            ->assertSee('Tipster Alpha', false)
            ->assertSee('event-tip-card-avatar-placeholder', false)
            ->assertSee('+42.50 EUR', false)
            ->assertSee('MATCH_RESULT · FT', false)
            ->assertSee(Selection::NAME_HOME, false)
            ->assertSee('2.15', false)
            ->assertSee('25.00 EUR', false)
            ->assertSee('MATCH_RESULT', false)
            ->getContent();

        $this->assertStringContainsString('form-icon--w', $html);
        $this->assertStringContainsString('form-icon--l', $html);
        $this->assertStringContainsString('event-tip-card-form', $html);

        $posTips = strpos($html, 'event-tips-section');
        $posMarkets = strpos($html, 'market-grid');
        $this->assertNotFalse($posTips);
        $this->assertNotFalse($posMarkets);
        $this->assertLessThan($posMarkets, $posTips);
    }

    public function test_event_page_orders_tips_by_wallet_total_result_desc(): void
    {
        ['event' => $event, 'odd' => $odd] = $this->seedEventWithOdd(92003);

        $leader = User::factory()->create(['name' => 'Leader Tipster']);
        $chaser = User::factory()->create(['name' => 'Chaser Tipster']);
        UserWallet::query()->where('user_id', $leader->id)->update(['total_result' => 500]);
        UserWallet::query()->where('user_id', $chaser->id)->update(['total_result' => 50]);

        ['event' => $pastEvent, 'odd' => $pastOdd] = $this->seedEventWithOdd(92013);

        foreach ([$chaser, $leader] as $user) {
            UserBet::query()->create([
                'user_id' => $user->id,
                'event_id' => $pastEvent->id,
                'odd_id' => $pastOdd->id,
                'stake' => 10,
                'odds_at_bet' => 2.0,
                'potential_return' => 20,
                'status' => UserBet::STATUS_WON,
            ]);
            UserBet::query()->create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'odd_id' => $odd->id,
                'stake' => 10,
                'odds_at_bet' => 2.15,
                'potential_return' => 21.5,
                'status' => UserBet::STATUS_PENDING,
            ]);
        }

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $posLeader = strpos($html, 'Leader Tipster');
        $posChaser = strpos($html, 'Chaser Tipster');
        $this->assertNotFalse($posLeader);
        $this->assertNotFalse($posChaser);
        $this->assertLessThan($posChaser, $posLeader);
    }

    public function test_event_page_hides_tips_for_users_with_no_resolved_bets(): void
    {
        ['event' => $event, 'odd' => $odd] = $this->seedEventWithOdd(92004);

        $pendingOnly = User::factory()->create(['name' => 'Pending Only']);
        $withHistory = User::factory()->create(['name' => 'Has Resolved History']);

        ['event' => $pastEvent, 'odd' => $pastOdd] = $this->seedEventWithOdd(92014);

        UserBet::query()->create([
            'user_id' => $withHistory->id,
            'event_id' => $pastEvent->id,
            'odd_id' => $pastOdd->id,
            'stake' => 10,
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'status' => UserBet::STATUS_LOST,
        ]);

        foreach ([$pendingOnly, $withHistory] as $user) {
            UserBet::query()->create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'odd_id' => $odd->id,
                'stake' => 15,
                'odds_at_bet' => 2.15,
                'potential_return' => 32.25,
                'status' => UserBet::STATUS_PENDING,
            ]);
        }

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Has Resolved History', false)
            ->assertDontSee('Pending Only', false);
    }

    public function test_event_page_hides_tips_section_when_no_bets(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92002);

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->assertDontSee('Player tips', false)
            ->getContent();

        $this->assertStringNotContainsString('<article class="event-tip-card">', $html);
    }
}
