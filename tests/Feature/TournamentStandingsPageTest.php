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

class TournamentStandingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_link_for_rank_one_tournament(): void
    {
        Tournament::query()->create([
            'name' => 'Premier League',
            'rank' => 1,
        ]);
        Tournament::query()->create([
            'name' => 'Other Cup',
            'rank' => 2,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Premier League', false)
            ->assertDontSee('Other Cup', false);
    }

    public function test_tournament_page_shows_standings_table(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Test League',
            'rank' => 1,
            'standings' => [
                'rows' => [
                    [
                        'position' => 1,
                        'team' => 'Alpha FC',
                        'played' => 5,
                        'won' => 3,
                        'drawn' => 1,
                        'lost' => 1,
                        'goals_for' => 10,
                        'goals_against' => 4,
                        'goal_difference' => 6,
                        'points' => 10,
                        'form' => 'Won 2-0 against FulhamLost 1-2 to Everton',
                    ],
                ],
            ],
        ]);

        $this->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('Test League', false)
            ->assertSee('Alpha FC', false)
            ->assertSee('form-icon--w', false)
            ->assertSee('form-icon--l', false)
            ->assertSee('Won 2-0 against Fulham', false)
            ->assertSee('Lost 1-2 to Everton', false);
    }

    public function test_tournament_page_empty_standings_message(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Empty League',
            'rank' => 1,
            'standings' => null,
        ]);

        $this->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('No standings data yet', false);
    }

    public function test_standings_promrel_applies_zone_row_classes(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Zone League',
            'rank' => 1,
            'standings' => [
                'rows' => [
                    [
                        'position' => 1,
                        'team' => 'Top Club',
                        'played' => 5,
                        'won' => 5,
                        'drawn' => 0,
                        'lost' => 0,
                        'goals_for' => 10,
                        'goals_against' => 0,
                        'goal_difference' => 10,
                        'points' => 15,
                        'form' => null,
                    ],
                    [
                        'position' => 20,
                        'team' => 'Bottom Club',
                        'played' => 5,
                        'won' => 0,
                        'drawn' => 0,
                        'lost' => 5,
                        'goals_for' => 0,
                        'goals_against' => 15,
                        'goal_difference' => -15,
                        'points' => 0,
                        'form' => null,
                    ],
                ],
            ],
            'standings_promrel' => [
                '1' => [
                    'type' => 'promotion',
                    'name' => 'Champions League (League Phase)',
                    'subtype' => 'champions-league',
                ],
                '20' => [
                    'type' => 'relegation',
                    'name' => 'Championship',
                ],
            ],
        ]);

        $html = $this->get(route('tournaments.show', $tournament))->assertOk()->getContent();
        $this->assertStringContainsString('standings-row--promotion-cl', $html);
        $this->assertStringContainsString('standings-row--relegation', $html);
        $this->assertStringContainsString('standings-pos-badge standings-pos-badge--cl', $html);
        $this->assertStringContainsString('standings-pos-badge standings-pos-badge--rel', $html);
        $this->assertStringContainsString('title="Champions League (League Phase)"', $html);
        $this->assertStringContainsString('title="Championship"', $html);
    }

    public function test_tournament_page_shows_upcoming_events_before_standings(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00', $tz));
        try {
            $tournament = Tournament::query()->create([
                'name' => 'Fixture League',
                'rank' => 1,
                'standings' => [
                    'rows' => [
                        [
                            'position' => 1,
                            'team' => 'Standings Row Club',
                            'played' => 1,
                            'won' => 1,
                            'drawn' => 0,
                            'lost' => 0,
                            'goals_for' => 1,
                            'goals_against' => 0,
                            'goal_difference' => 1,
                            'points' => 3,
                            'form' => null,
                        ],
                    ],
                ],
            ]);

            $home = Team::query()->create([
                'name' => 'Upcoming Home FC',
                'short_name' => 'UHF',
                'league' => 'FL',
                'country' => 'Testland',
                'tournament_id' => $tournament->id,
            ]);
            $away = Team::query()->create([
                'name' => 'Upcoming Away FC',
                'short_name' => 'UAF',
                'league' => 'FL',
                'country' => 'Testland',
                'tournament_id' => $tournament->id,
            ]);

            $event = Event::query()->create([
                'id' => 990001,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'tournament_id' => $tournament->id,
                'start_time' => Carbon::parse('2026-04-05 16:30:00', $tz),
                'status' => Event::STATUS_SCHEDULED,
            ]);

            $market = Market::query()->create([
                'id' => 990010,
                'event_id' => $event->id,
                'type' => Market::TYPE_MATCH_RESULT,
                'period' => Market::PERIOD_FULL_TIME,
                'line' => null,
                'status' => Market::STATUS_OPEN,
                'is_supported_market' => true,
            ]);

            $selectionId = 990020;
            $oddId = 990030;
            foreach ([
                Selection::NAME_HOME => 1.85,
                Selection::NAME_DRAW => 3.10,
                Selection::NAME_AWAY => 4.20,
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

            $html = $this->get(route('tournaments.show', $tournament))
                ->assertOk()
                ->assertSee('Upcoming Home FC', false)
                ->assertSee('Upcoming Away FC', false)
                ->assertSee('1.85', false)
                ->assertSee('Standings Row Club', false)
                ->getContent();

            $posUpcoming = strpos($html, 'Upcoming Home FC');
            $posStandings = strpos($html, 'Standings Row Club');
            $this->assertNotFalse($posUpcoming);
            $this->assertNotFalse($posStandings);
            $this->assertLessThan($posStandings, $posUpcoming);
        } finally {
            Carbon::setTestNow();
        }
    }
}
