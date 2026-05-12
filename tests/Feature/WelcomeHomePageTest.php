<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeHomePageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array{eventId: int, marketId: int, selectionIdStart: int, oddIdStart: int}  $ids
     */
    private function seedEventWithMatchOdds(
        Tournament $tournament,
        Team $home,
        Team $away,
        Carbon $startTime,
        array $ids,
    ): void {
        $event = Event::query()->create([
            'id' => $ids['eventId'],
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => $startTime,
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $ids['marketId'],
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selectionId = $ids['selectionIdStart'];
        $oddId = $ids['oddIdStart'];
        foreach ([
            Selection::NAME_HOME => 1.95,
            Selection::NAME_DRAW => 3.40,
            Selection::NAME_AWAY => 4.25,
        ] as $name => $price) {
            $selection = Selection::query()->create([
                'id' => $selectionId,
                'market_id' => $market->id,
                'name' => $name,
                'participant_id' => null,
                'handicap' => null,
                'created_at' => now(),
            ]);

            Odd::query()->create([
                'id' => $oddId,
                'selection_id' => $selection->id,
                'odds' => $price,
                'probability' => null,
                'is_active' => true,
                'created_at' => now(),
            ]);
            $selectionId++;
            $oddId++;
        }
    }

    public function test_home_lists_tournament_and_match_result_odds(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-01-10 10:00:00', $tz));
        try {
            $tournament = Tournament::query()->create([
                'name' => 'Welcome Test League',
                'rank' => 2,
                'country' => 'Testland',
            ]);

            $home = Team::query()->create([
                'name' => 'Alpha United',
                'short_name' => 'ALP',
                'league' => 'WTL',
                'country' => 'Testland',
                'tournament_id' => $tournament->id,
            ]);
            $away = Team::query()->create([
                'name' => 'Beta Town',
                'short_name' => 'BET',
                'league' => 'WTL',
                'country' => 'Testland',
                'tournament_id' => $tournament->id,
            ]);

            $this->seedEventWithMatchOdds(
                $tournament,
                $home,
                $away,
                Carbon::parse('2026-01-12 14:30:00', $tz),
                [
                    'eventId' => 770001,
                    'marketId' => 770010,
                    'selectionIdStart' => 770040,
                    'oddIdStart' => 770050,
                ],
            );

            $this->get('/')
                ->assertOk()
                ->assertSee('Monday, 12 January 2026', false)
                ->assertSee('14:30', false)
                ->assertSee('Welcome Test League', false)
                ->assertSee('Alpha United', false)
                ->assertSee('Beta Town', false)
                ->assertSee('1.95', false)
                ->assertSee('3.40', false)
                ->assertSee('4.25', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_home_uses_today_heading_for_same_day_fixtures(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-03-05 08:00:00', $tz));
        try {
            $tournament = Tournament::query()->create([
                'name' => 'Same Day League',
                'rank' => 2,
                'country' => 'Testland',
            ]);

            $home = Team::query()->create([
                'name' => 'Gamma FC',
                'short_name' => 'GAM',
                'league' => 'SDL',
                'country' => 'Testland',
                'tournament_id' => $tournament->id,
            ]);
            $away = Team::query()->create([
                'name' => 'Delta FC',
                'short_name' => 'DEL',
                'league' => 'SDL',
                'country' => 'Testland',
                'tournament_id' => $tournament->id,
            ]);

            $this->seedEventWithMatchOdds(
                $tournament,
                $home,
                $away,
                Carbon::parse('2026-03-05 19:45:00', $tz),
                [
                    'eventId' => 770101,
                    'marketId' => 770110,
                    'selectionIdStart' => 770140,
                    'oddIdStart' => 770150,
                ],
            );

            $this->get('/')
                ->assertOk()
                ->assertSee('Today', false)
                ->assertSee('19:45', false)
                ->assertDontSee('Thursday, 5 March 2026', false);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_home_groups_events_on_different_days_into_separate_sections(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-02-01 12:00:00', $tz));
        try {
            $tournament = Tournament::query()->create([
                'name' => 'Split Day League',
                'rank' => 2,
                'country' => 'Testland',
            ]);

            $home = Team::query()->create([
                'name' => 'Echo FC',
                'short_name' => 'ECH',
                'league' => 'SDL',
                'country' => 'Testland',
                'tournament_id' => $tournament->id,
            ]);
            $away = Team::query()->create([
                'name' => 'Foxtrot FC',
                'short_name' => 'FOX',
                'league' => 'SDL',
                'country' => 'Testland',
                'tournament_id' => $tournament->id,
            ]);

            $this->seedEventWithMatchOdds(
                $tournament,
                $home,
                $away,
                Carbon::parse('2026-02-03 18:00:00', $tz),
                [
                    'eventId' => 770201,
                    'marketId' => 770210,
                    'selectionIdStart' => 770240,
                    'oddIdStart' => 770250,
                ],
            );

            $this->seedEventWithMatchOdds(
                $tournament,
                $home,
                $away,
                Carbon::parse('2026-02-04 10:30:00', $tz),
                [
                    'eventId' => 770202,
                    'marketId' => 770220,
                    'selectionIdStart' => 770260,
                    'oddIdStart' => 770270,
                ],
            );

            $this->get('/')
                ->assertOk()
                ->assertSee('Tuesday, 3 February 2026', false)
                ->assertSee('Wednesday, 4 February 2026', false)
                ->assertSee('18:00', false)
                ->assertSee('10:30', false);
        } finally {
            Carbon::setTestNow();
        }
    }
}
