<?php

namespace Tests\Feature;

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
            'guardian_standings_url' => 'https://www.theguardian.com/football/test/table',
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
}
