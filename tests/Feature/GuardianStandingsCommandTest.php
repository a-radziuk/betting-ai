<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuardianStandingsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function minimalGuardianTableHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html><body>
<table>
<thead><tr>
<th><abbr title="Position">P</abbr></th>
<th>Team</th>
<th><abbr title="Games played">GP</abbr></th>
<th><abbr title="Won">W</abbr></th>
<th><abbr title="Drawn">D</abbr></th>
<th><abbr title="Lost">L</abbr></th>
<th><abbr title="Goals for">F</abbr></th>
<th><abbr title="Goals against">A</abbr></th>
<th><abbr title="Goal difference">GD</abbr></th>
<th><abbr title="Points">Pts</abbr></th>
<th><abbr title="Results of previous games">Form</abbr></th>
</tr></thead>
<tbody>
<tr>
<td>1</td>
<th scope="row"><a href="/football/test-fc">Test FC</a></th>
<td>10</td><td>5</td><td>3</td><td>2</td><td>12</td><td>8</td><td>4</td><td><b>18</b></td>
<td>Won last</td>
</tr>
</tbody>
</table>
</body></html>
HTML;
    }

    public function test_fetches_parses_and_saves_standings(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Test League',
            'country' => 'UK',
            'guardian_standings_url' => 'https://www.theguardian.com/football/test/table',
        ]);

        $team = Team::query()->create([
            'name' => 'Test FC',
            'short_name' => 'TF',
            'league' => 'TL',
            'country' => 'UK',
            'tournament_id' => $tournament->id,
            'guardian_name' => 'test fc',
        ]);

        Http::fake([
            'https://www.theguardian.com/football/test/table' => Http::response($this->minimalGuardianTableHtml(), 200),
        ]);

        $exit = Artisan::call('guardian:standings', ['tournamentId' => $tournament->id]);
        $this->assertSame(0, $exit);

        $tournament->refresh();
        $this->assertIsArray($tournament->standings);
        $this->assertArrayHasKey('rows', $tournament->standings);
        $this->assertCount(1, $tournament->standings['rows']);
        $row = $tournament->standings['rows'][0];
        $this->assertSame(1, $row['position']);
        $this->assertSame('Test FC', $row['team']);
        $this->assertSame('/football/test-fc', $row['team_path']);
        $this->assertSame(10, $row['played']);
        $this->assertSame(18, $row['points']);
        $this->assertSame('none', $row['movement']);
        $this->assertSame($team->id, $row['team_id']);
        $this->assertInstanceOf(Carbon::class, $tournament->standings_updated_at);
    }

    public function test_fails_when_tournament_missing(): void
    {
        Http::fake();

        $exit = Artisan::call('guardian:standings', ['tournamentId' => 999999]);

        $this->assertSame(1, $exit);
        Http::assertNothingSent();
    }

    public function test_fails_when_guardian_standings_url_missing(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'guardian_standings_url' => null,
        ]);

        Http::fake();

        $exit = Artisan::call('guardian:standings', ['tournamentId' => $tournament->id]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('guardian_standings_url', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_second_fetch_sets_movement_relative_to_saved_standings(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Mov League',
            'country' => 'UK',
            'guardian_standings_url' => 'https://www.theguardian.com/football/mov/table',
            'standings' => [
                'rows' => [
                    [
                        'position' => 1,
                        'team' => 'Test FC',
                        'team_path' => '/football/test-fc',
                        'played' => 10,
                        'won' => 5,
                        'drawn' => 3,
                        'lost' => 2,
                        'goals_for' => 12,
                        'goals_against' => 8,
                        'goal_difference' => 4,
                        'points' => 18,
                        'form' => null,
                    ],
                    [
                        'position' => 2,
                        'team' => 'Other FC',
                        'team_path' => '/football/other-fc',
                        'played' => 10,
                        'won' => 4,
                        'drawn' => 3,
                        'lost' => 3,
                        'goals_for' => 10,
                        'goals_against' => 9,
                        'goal_difference' => 1,
                        'points' => 15,
                        'form' => null,
                    ],
                ],
            ],
        ]);

        $teamTest = Team::query()->create([
            'name' => 'Test FC',
            'short_name' => 'T',
            'league' => 'ML',
            'country' => 'UK',
            'tournament_id' => $tournament->id,
            'guardian_name' => 'Test FC',
        ]);
        $teamOther = Team::query()->create([
            'name' => 'Other FC',
            'short_name' => 'O',
            'league' => 'ML',
            'country' => 'UK',
            'tournament_id' => $tournament->id,
            'guardian_name' => 'Other FC',
        ]);

        $html = <<<'HTML'
<!DOCTYPE html>
<html><body>
<table>
<thead><tr>
<th><abbr title="Position">P</abbr></th>
<th>Team</th>
<th><abbr title="Games played">GP</abbr></th>
<th><abbr title="Won">W</abbr></th>
<th><abbr title="Drawn">D</abbr></th>
<th><abbr title="Lost">L</abbr></th>
<th><abbr title="Goals for">F</abbr></th>
<th><abbr title="Goals against">A</abbr></th>
<th><abbr title="Goal difference">GD</abbr></th>
<th><abbr title="Points">Pts</abbr></th>
<th><abbr title="Results of previous games">Form</abbr></th>
</tr></thead>
<tbody>
<tr>
<td>1</td>
<th scope="row"><a href="/football/other-fc">Other FC</a></th>
<td>10</td><td>4</td><td>3</td><td>3</td><td>10</td><td>9</td><td>1</td><td><b>15</b></td>
<td>—</td>
</tr>
<tr>
<td>2</td>
<th scope="row"><a href="/football/test-fc">Test FC</a></th>
<td>10</td><td>5</td><td>3</td><td>2</td><td>12</td><td>8</td><td>4</td><td><b>18</b></td>
<td>—</td>
</tr>
</tbody>
</table>
</body></html>
HTML;

        Http::fake([
            'https://www.theguardian.com/football/mov/table' => Http::response($html, 200),
        ]);

        $exit = Artisan::call('guardian:standings', ['tournamentId' => $tournament->id]);
        $this->assertSame(0, $exit);

        $tournament->refresh();
        $rows = $tournament->standings['rows'];
        $this->assertCount(2, $rows);

        $byTeam = [];
        foreach ($rows as $r) {
            $byTeam[$r['team']] = $r['movement'];
        }

        $this->assertSame('up', $byTeam['Other FC']);
        $this->assertSame('down', $byTeam['Test FC']);

        $idsByTeam = [];
        foreach ($rows as $r) {
            $idsByTeam[$r['team']] = $r['team_id'];
        }
        $this->assertSame($teamOther->id, $idsByTeam['Other FC']);
        $this->assertSame($teamTest->id, $idsByTeam['Test FC']);
    }

    public function test_team_id_null_when_no_team_matches_guardian_name(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Unmatched League',
            'guardian_standings_url' => 'https://www.theguardian.com/football/unmatched/table',
        ]);

        Team::query()->create([
            'name' => 'Local Name',
            'short_name' => 'LN',
            'league' => 'UL',
            'country' => 'UK',
            'tournament_id' => $tournament->id,
            'guardian_name' => 'Different From Table',
        ]);

        Http::fake([
            'https://www.theguardian.com/football/unmatched/table' => Http::response($this->minimalGuardianTableHtml(), 200),
        ]);

        $exit = Artisan::call('guardian:standings', ['tournamentId' => $tournament->id]);
        $this->assertSame(0, $exit);

        $tournament->refresh();
        $row = $tournament->standings['rows'][0];
        $this->assertSame('Test FC', $row['team']);
        $this->assertNull($row['team_id']);
    }
}
