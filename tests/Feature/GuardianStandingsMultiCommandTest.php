<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuardianStandingsMultiCommandTest extends TestCase
{
    use RefreshDatabase;

    private function groupTableHtml(string $groupName, string $teamName, string $teamPath): string
    {
        return <<<HTML
<h3>{$groupName}</h3>
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
<th scope="row"><a href="{$teamPath}">{$teamName}</a></th>
<td>1</td><td>1</td><td>0</td><td>0</td><td>2</td><td>0</td><td>2</td><td><b>3</b></td>
<td>W</td>
</tr>
</tbody>
</table>
HTML;
    }

    private function multiGroupGuardianHtml(): string
    {
        return '<!DOCTYPE html><html><body>'
            .$this->groupTableHtml('Group A', 'Alpha FC', '/football/alpha-fc')
            .$this->groupTableHtml('Group B', 'Beta FC', '/football/beta-fc')
            .'</body></html>';
    }

    public function test_fetches_parses_and_saves_group_standings(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'World Cup',
            'country' => 'INT',
            'guardian_standings_url' => 'https://www.theguardian.com/football/world-cup-2026/table',
        ]);

        $teamAlpha = Team::query()->create([
            'name' => 'Alpha',
            'short_name' => 'A',
            'league' => 'WC',
            'country' => 'INT',
            'tournament_id' => $tournament->id,
            'guardian_name' => 'Alpha FC',
        ]);
        $teamBeta = Team::query()->create([
            'name' => 'Beta',
            'short_name' => 'B',
            'league' => 'WC',
            'country' => 'INT',
            'tournament_id' => $tournament->id,
            'guardian_name' => 'Beta FC',
        ]);

        Http::fake([
            'https://www.theguardian.com/football/world-cup-2026/table' => Http::response($this->multiGroupGuardianHtml(), 200),
        ]);

        $exit = Artisan::call('guardian:standings-multi', ['tournamentId' => $tournament->id]);
        $this->assertSame(0, $exit);

        $tournament->refresh();
        $this->assertIsArray($tournament->standings);
        $this->assertArrayHasKey('groups', $tournament->standings);
        $this->assertCount(2, $tournament->standings['groups']);

        $groupA = $tournament->standings['groups'][0];
        $this->assertSame('Group A', $groupA['name']);
        $this->assertCount(1, $groupA['rows']);
        $this->assertSame('Alpha FC', $groupA['rows'][0]['team']);
        $this->assertSame(3, $groupA['rows'][0]['points']);
        $this->assertSame('none', $groupA['rows'][0]['movement']);
        $this->assertSame($teamAlpha->id, $groupA['rows'][0]['team_id']);

        $groupB = $tournament->standings['groups'][1];
        $this->assertSame('Group B', $groupB['name']);
        $this->assertSame('Beta FC', $groupB['rows'][0]['team']);
        $this->assertSame($teamBeta->id, $groupB['rows'][0]['team_id']);

        $this->assertInstanceOf(Carbon::class, $tournament->standings_updated_at);
    }

    public function test_fails_when_tournament_missing(): void
    {
        Http::fake();

        $exit = Artisan::call('guardian:standings-multi', ['tournamentId' => 999999]);

        $this->assertSame(1, $exit);
        Http::assertNothingSent();
    }

    public function test_fails_when_guardian_standings_url_missing(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'World Cup',
            'guardian_standings_url' => null,
        ]);

        Http::fake();

        $exit = Artisan::call('guardian:standings-multi', ['tournamentId' => $tournament->id]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('guardian_standings_url', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_second_fetch_sets_movement_per_group(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'World Cup',
            'guardian_standings_url' => 'https://www.theguardian.com/football/world-cup-move/table',
            'standings' => [
                'groups' => [
                    [
                        'name' => 'Group A',
                        'rows' => [
                            [
                                'position' => 1,
                                'team' => 'Alpha FC',
                                'team_path' => '/football/alpha-fc',
                                'played' => 1,
                                'won' => 1,
                                'drawn' => 0,
                                'lost' => 0,
                                'goals_for' => 2,
                                'goals_against' => 0,
                                'goal_difference' => 2,
                                'points' => 3,
                                'form' => null,
                            ],
                            [
                                'position' => 2,
                                'team' => 'Gamma FC',
                                'team_path' => '/football/gamma-fc',
                                'played' => 1,
                                'won' => 0,
                                'drawn' => 0,
                                'lost' => 1,
                                'goals_for' => 0,
                                'goals_against' => 2,
                                'goal_difference' => -2,
                                'points' => 0,
                                'form' => null,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $html = '<!DOCTYPE html><html><body>'
            .'<h3>Group A</h3>'
            .'<table><thead><tr>'
            .'<th><abbr title="Position">P</abbr></th><th>Team</th>'
            .'<th><abbr title="Games played">GP</abbr></th><th><abbr title="Won">W</abbr></th>'
            .'<th><abbr title="Drawn">D</abbr></th><th><abbr title="Lost">L</abbr></th>'
            .'<th><abbr title="Goals for">F</abbr></th><th><abbr title="Goals against">A</abbr></th>'
            .'<th><abbr title="Goal difference">GD</abbr></th><th><abbr title="Points">Pts</abbr></th>'
            .'<th><abbr title="Results of previous games">Form</abbr></th>'
            .'</tr></thead><tbody>'
            .'<tr><td>1</td><th scope="row"><a href="/football/gamma-fc">Gamma FC</a></th>'
            .'<td>2</td><td>1</td><td>0</td><td>1</td><td>2</td><td>2</td><td>0</td><td><b>3</b></td><td>—</td></tr>'
            .'<tr><td>2</td><th scope="row"><a href="/football/alpha-fc">Alpha FC</a></th>'
            .'<td>2</td><td>1</td><td>0</td><td>1</td><td>3</td><td>2</td><td>1</td><td><b>3</b></td><td>—</td></tr>'
            .'</tbody></table>'
            .'</body></html>';

        Http::fake([
            'https://www.theguardian.com/football/world-cup-move/table' => Http::response($html, 200),
        ]);

        $exit = Artisan::call('guardian:standings-multi', ['tournamentId' => $tournament->id]);
        $this->assertSame(0, $exit);

        $tournament->refresh();
        $rows = $tournament->standings['groups'][0]['rows'];
        $byTeam = [];
        foreach ($rows as $row) {
            $byTeam[$row['team']] = $row['movement'];
        }

        $this->assertSame('up', $byTeam['Gamma FC']);
        $this->assertSame('down', $byTeam['Alpha FC']);
    }
}
