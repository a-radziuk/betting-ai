<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BbcStandingsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function minimalBbcTableHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html><body>
<table data-testid="football-table">
<thead><tr>
<th><span class="visually-hidden">Team</span></th>
<th><span class="visually-hidden">Played</span></th>
<th><span class="visually-hidden">Won</span></th>
<th><span class="visually-hidden">Drawn</span></th>
<th><span class="visually-hidden">Lost</span></th>
<th><span class="visually-hidden">Goals For</span></th>
<th><span class="visually-hidden">Goals Against</span></th>
<th><span class="visually-hidden">Goal Difference</span></th>
<th><span class="visually-hidden">Points</span></th>
<th><span class="visually-hidden">Form, Last 6 games, Oldest first</span></th>
</tr></thead>
<tbody>
<tr>
<td aria-label="Team"><span class="ssrcss-1mnw0cb-Rank">1</span><span data-600="Tromsø" aria-hidden="true"></span><span class="visually-hidden">Tromsø</span></td>
<td aria-label="Played">14</td>
<td aria-label="Won">8</td>
<td aria-label="Drawn">4</td>
<td aria-label="Lost">2</td>
<td aria-label="Goals For">22</td>
<td aria-label="Goals Against">14</td>
<td aria-label="Goal Difference">8</td>
<td aria-label="Points"><span>28</span></td>
<td aria-label="Form, Last 6 games, Oldest first"><ul><li><div data-testid="letter-content">D</div></li><li><div data-testid="letter-content">W</div></li></ul></td>
</tr>
</tbody>
</table>
</body></html>
HTML;
    }

    public function test_fetches_parses_and_saves_standings(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Eliteserien',
            'country' => 'Norway',
            'bbc_standings_url' => 'https://www.bbc.com/sport/football/norwegian-tippeligaen/table',
        ]);

        $team = Team::query()->create([
            'name' => 'Tromso',
            'short_name' => 'TRO',
            'league' => 'Norway. Eliteserien',
            'country' => 'Norway',
            'tournament_id' => $tournament->id,
            'external_name' => 'Tromsø',
        ]);

        Http::fake([
            'https://www.bbc.com/sport/football/norwegian-tippeligaen/table' => Http::response($this->minimalBbcTableHtml(), 200),
        ]);

        $exit = Artisan::call('bbc:standings', ['tournamentId' => $tournament->id]);
        $this->assertSame(0, $exit);

        $tournament->refresh();
        $this->assertIsArray($tournament->standings);
        $this->assertArrayHasKey('rows', $tournament->standings);
        $this->assertCount(1, $tournament->standings['rows']);
        $row = $tournament->standings['rows'][0];
        $this->assertSame(1, $row['position']);
        $this->assertSame('Tromsø', $row['team']);
        $this->assertSame(14, $row['played']);
        $this->assertSame(28, $row['points']);
        $this->assertSame('DW', $row['form']);
        $this->assertSame('none', $row['movement']);
        $this->assertSame($team->id, $row['team_id']);
        $this->assertInstanceOf(Carbon::class, $tournament->standings_updated_at);
    }

    public function test_strips_trailing_slash_from_bbc_standings_url(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Eliteserien',
            'country' => 'Norway',
            'bbc_standings_url' => 'https://www.bbc.com/sport/football/norwegian-tippeligaen/table/',
        ]);

        Http::fake([
            'https://www.bbc.com/sport/football/norwegian-tippeligaen/table' => Http::response($this->minimalBbcTableHtml(), 200),
        ]);

        $exit = Artisan::call('bbc:standings', ['tournamentId' => $tournament->id]);

        $this->assertSame(0, $exit);
        Http::assertSent(fn ($request) => $request->url() === 'https://www.bbc.com/sport/football/norwegian-tippeligaen/table');
    }

    public function test_fails_when_tournament_missing(): void
    {
        Http::fake();

        $exit = Artisan::call('bbc:standings', ['tournamentId' => 999999]);

        $this->assertSame(1, $exit);
        Http::assertNothingSent();
    }

    public function test_fails_when_bbc_standings_url_missing(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Eliteserien',
            'bbc_standings_url' => null,
        ]);

        Http::fake();

        $exit = Artisan::call('bbc:standings', ['tournamentId' => $tournament->id]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('bbc_standings_url', Artisan::output());
        Http::assertNothingSent();
    }
}
