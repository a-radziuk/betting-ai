<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAnalysis;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
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

    private function userWhoCanPlaceBets(): User
    {
        return User::factory()->create([
            'priveleges' => User::PRIVELEGE_PLACE_BETS.','.User::PRIVELEGE_SEE_TIPS,
        ]);
    }

    /**
     * @return array{event: Event, odd: Odd, player: User}
     */
    private function seedEventWithTipFromPlayer(int $eventId, string $playerName = 'Tipster Alpha'): array
    {
        ['event' => $event, 'odd' => $odd] = $this->seedEventWithOdd($eventId);

        $player = User::factory()->create(['name' => $playerName]);
        UserWallet::query()->where('user_id', $player->id)->update(['total_result' => 42.5]);

        ['event' => $pastEvent, 'odd' => $pastOdd] = $this->seedEventWithOdd($eventId + 10);

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
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 25,
            'odds_at_bet' => 2.15,
            'potential_return' => 53.75,
            'status' => UserBet::STATUS_PENDING,
        ]);

        return ['event' => $event, 'odd' => $odd, 'player' => $player];
    }

    public function test_guest_cannot_see_odds_section(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92040);

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<section class="market-grid"', $html);
    }

    public function test_user_with_place_bets_privilege_sees_odds_section(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92041);
        $viewer = $this->userWhoCanPlaceBets();

        $html = $this->actingAs($viewer)
            ->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<section class="market-grid"', $html);
        $this->assertStringContainsString('place-bet', $html);
    }

    public function test_user_without_place_bets_privilege_cannot_see_odds_section(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92042);
        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $html = $this->actingAs($viewer)
            ->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<section class="market-grid"', $html);
    }

    public function test_superadmin_sees_odds_section(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92043);
        $admin = User::factory()->create([
            'is_superadmin' => true,
            'priveleges' => null,
        ]);

        $html = $this->actingAs($admin)
            ->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<section class="market-grid"', $html);
    }

    public function test_guest_sees_subscribe_link_instead_of_tip_pick_details(): void
    {
        ['event' => $event, 'player' => $player] = $this->seedEventWithTipFromPlayer(92050);

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Subscribe to see the tips', false)
            ->assertSee('Tipster Alpha', false)
            ->assertSee(route('subscribe'), false)
            ->getContent();

        $this->assertStringNotContainsString('<dl class="event-tip-card-pick">', $html);
    }

    public function test_user_without_see_tips_privilege_sees_subscribe_link(): void
    {
        ['event' => $event, 'player' => $player] = $this->seedEventWithTipFromPlayer(92051);

        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_PLACE_BETS,
        ]);

        $html = $this->actingAs($viewer)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Subscribe to see the tips', false)
            ->getContent();

        $this->assertStringNotContainsString('<dl class="event-tip-card-pick">', $html);
        $this->assertStringContainsString(route('subscribe'), $html);
    }

    public function test_user_with_see_tips_privilege_sees_pick_details(): void
    {
        ['event' => $event] = $this->seedEventWithTipFromPlayer(92052);

        $html = $this->actingAs(User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]))
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('MATCH_RESULT · FT', false)
            ->assertSee(Selection::NAME_HOME, false)
            ->assertSee('2.15', false)
            ->getContent();

        $this->assertStringContainsString('<dl class="event-tip-card-pick">', $html);
        $this->assertStringNotContainsString('Subscribe to see the tips', $html);
    }

    public function test_event_page_shows_user_bets_before_markets(): void
    {
        ['event' => $event] = $this->seedEventWithTipFromPlayer(92001, 'Tipster Alpha');

        $viewer = $this->userWhoCanPlaceBets();

        $html = $this->actingAs($viewer)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Player tips', false)
            ->assertSee('Tipster Alpha', false)
            ->assertSee('event-tip-card-avatar-placeholder', false)
            ->assertSee('+42.50', false)
            ->assertDontSee('Total result', false)
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

    public function test_event_page_shows_strongest_analysis_before_markets(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92020);

        EventAnalysis::query()->create([
            'event_id' => $event->id,
            'type' => EventAnalysis::TYPE_GPT1,
            'strength' => 4,
            'event_name' => 'Weak analysis',
            'likely_outcome' => EventAnalysis::LIKELY_OUTCOME_DRAW,
            'approximate_goals' => 1,
            'description' => 'Lower strength should not appear.',
            'home_motivation' => 3,
            'away_motivation' => 3,
            'home_class' => 3,
            'away_class' => 3,
        ]);

        EventAnalysis::query()->create([
            'event_id' => $event->id,
            'type' => EventAnalysis::TYPE_MANUAL,
            'strength' => EventAnalysis::STRENGTH_MAX,
            'event_name' => 'Event Home vs Event Away',
            'likely_outcome' => EventAnalysis::LIKELY_OUTCOME_AWAY_WIN,
            'approximate_goals' => 2,
            'description' => 'Augsburg chase European places.',
            'home_motivation' => 2,
            'away_motivation' => 6,
            'home_class' => 4,
            'away_class' => 5,
            'influenced_by' => ['Other Fixture'],
            'influenced_by_event_ids' => ['92021'],
        ]);

        $html = $this->actingAs($this->userWhoCanPlaceBets())
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Match analysis', false)
            ->assertSee('Away win', false)
            ->assertSee('Augsburg chase European places.', false)
            ->assertSee('Other Fixture', false)
            ->assertDontSee('Lower strength should not appear.', false)
            ->getContent();

        $posAnalysis = strpos($html, 'event-analysis-section');
        $posMarkets = strpos($html, 'market-grid');
        $this->assertNotFalse($posAnalysis);
        $this->assertNotFalse($posMarkets);
        $this->assertLessThan($posMarkets, $posAnalysis);
    }

    public function test_event_page_shows_influenced_by_from_event_ids_when_labels_missing(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92023);
        $relatedEventId = 92024;
        $this->seedEventWithOdd($relatedEventId);

        EventAnalysis::query()->create([
            'event_id' => $event->id,
            'type' => EventAnalysis::TYPE_MANUAL,
            'strength' => EventAnalysis::STRENGTH_MAX,
            'event_name' => 'Event Home vs Event Away',
            'likely_outcome' => EventAnalysis::LIKELY_OUTCOME_DRAW,
            'approximate_goals' => 2,
            'description' => 'Related fixture matters.',
            'home_motivation' => 5,
            'away_motivation' => 5,
            'home_class' => 5,
            'away_class' => 5,
            'influenced_by' => null,
            'influenced_by_event_ids' => [(string) $relatedEventId],
        ]);

        $this->actingAs($this->userWhoCanPlaceBets())
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Can be influenced by', false)
            ->assertSee('Event Home vs Event Away', false)
            ->assertSee(route('events.show', $relatedEventId), false);
    }

    public function test_event_page_hides_analysis_when_none_exist(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92022);

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<section class="event-analysis-section"', $html);
    }

    public function test_event_page_shows_tournament_standings_before_markets(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Bundesliga',
            'rank' => 1,
            'standings' => [
                'rows' => [
                    [
                        'position' => 1,
                        'team' => 'Alpha FC',
                        'team_display_name' => 'Alpha FC',
                        'played' => 5,
                        'won' => 3,
                        'drawn' => 1,
                        'lost' => 1,
                        'goals_for' => 10,
                        'goals_against' => 4,
                        'goal_difference' => 6,
                        'points' => 10,
                        'form' => null,
                    ],
                ],
            ],
        ]);

        ['event' => $event] = $this->seedEventWithOdd(92031);
        $event->update(['tournament_id' => $tournament->id]);

        $html = $this->actingAs($this->userWhoCanPlaceBets())
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Bundesliga', false)
            ->assertSee('Alpha FC', false)
            ->assertSee('League standings', false)
            ->assertSee('Full league page', false)
            ->getContent();

        $posStandings = strpos($html, 'event-page-standings');
        $posMarkets = strpos($html, 'market-grid');
        $this->assertNotFalse($posStandings);
        $this->assertNotFalse($posMarkets);
        $this->assertLessThan($posMarkets, $posStandings);
    }

    public function test_event_page_hides_standings_when_event_has_no_tournament(): void
    {
        ['event' => $event] = $this->seedEventWithOdd(92032);

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<section class="card event-page-standings"', $html);
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
