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
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EventExportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_flat_json_rows(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'standings' => [
                'rows' => [
                    ['position' => 1, 'team' => 'Alpha United', 'played' => 3, 'points' => 9],
                ],
            ],
        ]);
        $home = Team::query()->create([
            'name' => 'H',
            'short_name' => 'H',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A',
            'short_name' => 'A',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);

        $startTime = Carbon::parse('2026-06-15 18:30:00', 'UTC');

        Event::query()->create([
            'id' => 99001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => $startTime,
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 99002,
            'event_id' => 99001,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => 99003,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        Odd::query()->create([
            'id' => 99004,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99001]);
        $output = Artisan::output();

        $this->assertSame(0, $exit);

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);

        $this->assertSame('99001', $decoded['eventId']);
        $this->assertSame('H vs A', $decoded['eventName']);
        $this->assertSame('Premier League', $decoded['eventTournament']);
        $this->assertSame($startTime->toIso8601String(), $decoded['eventDateTime']);

        $this->assertArrayHasKey('standings', $decoded);
        $this->assertIsArray($decoded['standings']);
        $this->assertTrue(array_is_list($decoded['standings']));
        $this->assertCount(1, $decoded['standings']);
        $this->assertSame(1, $decoded['standings'][0]['position']);
        $this->assertSame('Alpha United', $decoded['standings'][0]['team']);
        $this->assertSame(9, $decoded['standings'][0]['points']);

        $this->assertArrayHasKey('odds', $decoded);
        $this->assertTrue(array_is_list($decoded['odds']));
        $this->assertCount(1, $decoded['odds']);

        $row = $decoded['odds'][0];
        $this->assertSame(Market::TYPE_MATCH_RESULT, $row['type']);
        $this->assertSame(Market::PERIOD_FULL_TIME, $row['period']);
        $this->assertSame('HOME', $row['selection']);
        $this->assertSame(2.5, $row['odds']);
    }

    public function test_playoff_tournament_includes_standings(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Champions League Playoffs',
            'is_playoff' => true,
            'standings' => [
                'rows' => [
                    ['position' => 1, 'team' => 'Alpha United', 'played' => 3, 'points' => 9],
                ],
            ],
        ]);
        $home = Team::query()->create([
            'name' => 'H',
            'short_name' => 'H',
            'league' => 'Europe',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A',
            'short_name' => 'A',
            'league' => 'Europe',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => 99005,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::parse('2026-06-15 18:30:00', 'UTC'),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99005, '--no-odds' => true]);
        $this->assertSame(0, $exit);

        $decoded = json_decode(Artisan::output(), true);
        $this->assertIsArray($decoded);
        $this->assertSame('Champions League Playoffs', $decoded['eventTournament']);
        $this->assertArrayHasKey('standings', $decoded);
        $this->assertIsArray($decoded['standings']);
        $this->assertCount(1, $decoded['standings']);
        $this->assertSame('Alpha United', $decoded['standings'][0]['team']);
        $this->assertArrayNotHasKey('outcome', $decoded['standings'][0]);
        $this->assertArrayNotHasKey('outcome_positivity', $decoded['standings'][0]);
        $this->assertArrayNotHasKey('remaining_games', $decoded['standings'][0]);
        $this->assertArrayNotHasKey('potential_points', $decoded['standings'][0]);
    }

    public function test_exports_grouped_standings_with_group_labels(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'World Cup',
            'standings' => [
                'groups' => [
                    [
                        'name' => 'Group A',
                        'rows' => [
                            ['position' => 1, 'team' => 'Alpha FC', 'played' => 1, 'points' => 3],
                            ['position' => 2, 'team' => 'Beta FC', 'played' => 1, 'points' => 1],
                        ],
                    ],
                    [
                        'name' => 'Group B',
                        'rows' => [
                            ['position' => 1, 'team' => 'Gamma FC', 'played' => 0, 'points' => 0],
                        ],
                    ],
                ],
            ],
            'standings_promrel' => [
                '1' => [
                    'type' => 'promotion',
                    'name' => 'Knockout',
                    'positivity' => 10,
                ],
            ],
        ]);
        $home = Team::query()->create([
            'name' => 'H',
            'short_name' => 'H',
            'league' => 'World',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A',
            'short_name' => 'A',
            'league' => 'World',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => 99010,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::parse('2026-06-15 18:30:00', 'UTC'),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99010, '--no-odds' => true]);
        $this->assertSame(0, $exit);

        $decoded = json_decode(Artisan::output(), true);
        $this->assertIsArray($decoded['standings']);
        $this->assertCount(3, $decoded['standings']);
        $this->assertSame('Group A', $decoded['standings'][0]['group']);
        $this->assertSame('Alpha FC', $decoded['standings'][0]['team']);
        $this->assertSame('Promotion to Knockout', $decoded['standings'][0]['outcome']);
        $this->assertSame(0, $decoded['standings'][0]['remaining_games']);
        $this->assertSame('Group A', $decoded['standings'][1]['group']);
        $this->assertSame('Beta FC', $decoded['standings'][1]['team']);
        $this->assertSame('Group B', $decoded['standings'][2]['group']);
        $this->assertSame(0, $decoded['standings'][2]['position']);
        $this->assertSame('Gamma FC', $decoded['standings'][2]['team']);
        $this->assertSame(0, $decoded['standings'][2]['remaining_games']);
        $this->assertArrayNotHasKey('outcome', $decoded['standings'][2]);
        $this->assertArrayNotHasKey('outcome_positivity', $decoded['standings'][2]);
    }

    public function test_unplayed_standings_row_has_zero_position_and_no_outcome_fields(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'League',
            'standings' => [
                'rows' => [
                    ['position' => 4, 'team' => 'Not Started FC', 'played' => 0, 'points' => 0],
                ],
            ],
            'standings_promrel' => [
                '4' => [
                    'type' => 'relegation',
                    'name' => 'Second Division',
                    'positivity' => -5,
                ],
            ],
        ]);
        $home = Team::query()->create([
            'name' => 'H',
            'short_name' => 'H',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A',
            'short_name' => 'A',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => 99011,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::parse('2026-06-15 18:30:00', 'UTC'),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99011, '--no-odds' => true]);
        $this->assertSame(0, $exit);

        $row = json_decode(Artisan::output(), true)['standings'][0];
        $this->assertSame(0, $row['position']);
        $this->assertSame('Not Started FC', $row['team']);
        $this->assertArrayNotHasKey('outcome', $row);
        $this->assertArrayNotHasKey('outcome_positivity', $row);
    }

    public function test_no_markets_excludes_listed_types_case_insensitively(): void
    {
        $tournament = Tournament::query()->create(['name' => 'Div One']);
        $home = Team::query()->create([
            'name' => 'H2',
            'short_name' => 'H2',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A2',
            'short_name' => 'A2',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);

        $startTime = Carbon::parse('2026-06-16 19:00:00', 'UTC');

        Event::query()->create([
            'id' => 99200,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => $startTime,
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $mrMarket = Market::query()->create([
            'id' => 99201,
            'event_id' => 99200,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $mrSel = Selection::query()->create([
            'id' => 99202,
            'market_id' => $mrMarket->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 99203,
            'selection_id' => $mrSel->id,
            'odds' => 2.1,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $hcMarket = Market::query()->create([
            'id' => 99204,
            'event_id' => 99200,
            'type' => Market::TYPE_HANDICAP,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $hcSel = Selection::query()->create([
            'id' => 99205,
            'market_id' => $hcMarket->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => -0.25,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 99206,
            'selection_id' => $hcSel->id,
            'odds' => 1.95,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99200]);
        $this->assertSame(0, $exit);
        $full = json_decode(Artisan::output(), true);
        $this->assertArrayHasKey('standings', $full);
        $this->assertNull($full['standings']);
        $this->assertCount(2, $full['odds']);

        $exit = Artisan::call('event:export', [
            'eventId' => 99200,
            '--no-markets' => ' HANDICAP , correct_score ',
        ]);
        $this->assertSame(0, $exit);
        $filtered = json_decode(Artisan::output(), true);
        $this->assertCount(1, $filtered['odds']);
        $this->assertSame(Market::TYPE_MATCH_RESULT, $filtered['odds'][0]['type']);

        $exit = Artisan::call('event:export', [
            'eventId' => 99200,
            '--no-markets' => 'MATCH_RESULT,HANDICAP',
        ]);
        $this->assertSame(0, $exit);
        $emptyOdds = json_decode(Artisan::output(), true);
        $this->assertCount(0, $emptyOdds['odds']);
    }

    public function test_no_odds_omits_odds_from_export(): void
    {
        $tournament = Tournament::query()->create(['name' => 'No Odds League']);
        $home = Team::query()->create([
            'name' => 'H3',
            'short_name' => 'H3',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A3',
            'short_name' => 'A3',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => 99300,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::parse('2026-06-17 20:00:00', 'UTC'),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 99301,
            'event_id' => 99300,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 99302,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 99303,
            'selection_id' => $selection->id,
            'odds' => 1.85,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $exit = Artisan::call('event:export', [
            'eventId' => 99300,
            '--no-odds' => true,
        ]);
        $decoded = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exit);
        $this->assertSame('99300', $decoded['eventId']);
        $this->assertSame('H3 vs A3', $decoded['eventName']);
        $this->assertArrayHasKey('odds', $decoded);
        $this->assertSame([], $decoded['odds']);
    }

    public function test_includes_fifa_fields_for_world_teams_when_present(): void
    {
        $tournament = Tournament::query()->create(['name' => 'World Cup']);
        $home = Team::query()->create([
            'name' => 'Argentina',
            'short_name' => 'ARG',
            'league' => 'INT',
            'country' => 'World',
            'tournament_id' => $tournament->id,
            'fifa_name' => 'Argentina',
            'fifa_rank' => 1,
            'fifa_points' => 1889.06,
        ]);
        $away = Team::query()->create([
            'name' => 'France',
            'short_name' => 'FRA',
            'league' => 'INT',
            'country' => 'World',
            'tournament_id' => $tournament->id,
            'fifa_name' => 'France',
            'fifa_rank' => 2,
            'fifa_points' => 1887.11,
        ]);

        Event::query()->create([
            'id' => 99400,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::parse('2026-06-18 18:00:00', 'UTC'),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99400, '--no-odds' => true]);
        $decoded = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exit);
        $this->assertSame(['fifa_rank' => 1, 'fifa_points' => 1889.06], $decoded['homeTeam']);
        $this->assertSame(['fifa_rank' => 2, 'fifa_points' => 1887.11], $decoded['awayTeam']);
    }

    public function test_omits_fifa_fields_for_non_world_teams_or_missing_values(): void
    {
        $tournament = Tournament::query()->create(['name' => 'Premier League']);
        $home = Team::query()->create([
            'name' => 'Club Home',
            'short_name' => 'CH',
            'league' => 'England',
            'country' => 'England',
            'tournament_id' => $tournament->id,
            'fifa_rank' => 10,
            'fifa_points' => 1500.00,
        ]);
        $away = Team::query()->create([
            'name' => 'World Away',
            'short_name' => 'WA',
            'league' => 'INT',
            'country' => 'World',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => 99401,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::parse('2026-06-18 19:00:00', 'UTC'),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $exit = Artisan::call('event:export', ['eventId' => 99401, '--no-odds' => true]);
        $decoded = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exit);
        $this->assertArrayNotHasKey('homeTeam', $decoded);
        $this->assertArrayNotHasKey('awayTeam', $decoded);
    }

    public function test_fails_when_event_missing(): void
    {
        $exit = Artisan::call('event:export', ['eventId' => 99999999]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Event not found', Artisan::output());
    }
}
