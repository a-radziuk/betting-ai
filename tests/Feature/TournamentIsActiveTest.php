<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentIsActiveTest extends TestCase
{
    use RefreshDatabase;

    private function seedUpcomingEvent(Tournament $tournament, int $eventId = 880001): Event
    {
        $home = Team::query()->create([
            'name' => 'Active Home',
            'short_name' => 'AHM',
            'league' => 'TST',
            'country' => 'Testland',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'Active Away',
            'short_name' => 'AAY',
            'league' => 'TST',
            'country' => 'Testland',
            'tournament_id' => $tournament->id,
        ]);

        $event = Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $eventId + 10,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId + 20,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        Odd::query()->create([
            'id' => $eventId + 30,
            'selection_id' => $selection->id,
            'odds' => 1.90,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return $event;
    }

    public function test_inactive_tournament_is_hidden_from_homepage_and_public_pages(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00', $tz));
        try {
            $inactiveTournament = Tournament::query()->create([
                'name' => 'Hidden League',
                'rank' => 1,
                'country' => 'Testland',
                'is_active' => false,
            ]);
            $event = $this->seedUpcomingEvent($inactiveTournament, 880101);

            $this->get('/')
                ->assertOk()
                ->assertDontSee('Hidden League', false)
                ->assertDontSee('Active Home', false);

            $this->get(route('tournaments.show', $inactiveTournament))
                ->assertNotFound();

            $this->get(route('tournaments.results', $inactiveTournament))
                ->assertNotFound();

            $this->get(route('events.show', $event))
                ->assertNotFound();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_inactive_tournament_events_cannot_be_bet_on(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Hidden League',
            'country' => 'Testland',
            'is_active' => false,
        ]);
        $event = $this->seedUpcomingEvent($tournament, 880201);
        $odd = Odd::query()->find(880231);
        $this->assertNotNull($odd);

        $user = User::factory()->create([
            'priveleges' => User::PRIVELEGE_PLACE_BETS,
        ]);

        $this->actingAs($user)
            ->get(route('bets.place.show', $odd))
            ->assertNotFound();
    }

    public function test_active_tournament_remains_visible_on_homepage(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00', $tz));
        try {
            $tournament = Tournament::query()->create([
                'name' => 'Visible League',
                'rank' => 1,
                'country' => 'Testland',
                'is_active' => true,
            ]);
            $this->seedUpcomingEvent($tournament, 880301);

            $this->get('/')
                ->assertOk()
                ->assertSee('Visible League', false)
                ->assertSee('Active Home', false);
        } finally {
            Carbon::setTestNow();
        }
    }
}
