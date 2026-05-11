<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuardianResultsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function minimalResultsHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html><body>
<a href="#" class="dcr-12nh7p9"><span class="dcr-yb9mnm">FT</span><div class="dcr-3l4pru"><span class="dcr-iqim6o">North FC</span></div><span class="dcr-17v2nd5"><span class="dcr-79z44d">2</span><span class="dcr-13mkt9n"></span><span class="dcr-1c2czlv">0</span></span><div class="dcr-rm7qtf"><picture></picture>South FC</div></a>
</body></html>
HTML;
    }

    public function test_settles_matching_event(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Test League',
            'country' => 'England',
            'rank' => 1,
            'guardian_results_url' => 'https://www.theguardian.com/football/testleague/results',
        ]);

        $home = Team::query()->create([
            'name' => 'North FC',
            'short_name' => 'NOR',
            'league' => 'TL',
            'country' => 'England',
            'guardian_name' => 'North FC',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'South FC',
            'short_name' => 'SOU',
            'league' => 'TL',
            'country' => 'England',
            'guardian_name' => 'South FC',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => 660001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_SCHEDULED,
            'score' => null,
        ]);

        Http::fake([
            'https://www.theguardian.com/football/testleague/results' => Http::response($this->minimalResultsHtml(), 200),
        ]);

        $exit = Artisan::call('guardian:results', ['tournamentId' => $tournament->id]);

        $this->assertSame(0, $exit);

        $event = Event::query()->find(660001);
        $this->assertSame('2:0', $event->score);
        $this->assertSame(Event::STATUS_FINISHED, $event->status);
    }

    public function test_skips_already_settled_event(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Test League',
            'country' => 'England',
            'rank' => 1,
            'guardian_results_url' => 'https://www.theguardian.com/football/testleague/results',
        ]);

        $home = Team::query()->create([
            'name' => 'North FC',
            'short_name' => 'NOR',
            'league' => 'TL',
            'country' => 'England',
            'guardian_name' => 'North FC',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'South FC',
            'short_name' => 'SOU',
            'league' => 'TL',
            'country' => 'England',
            'guardian_name' => 'South FC',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => 660002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '1:1',
        ]);

        Http::fake([
            'https://www.theguardian.com/football/testleague/results' => Http::response($this->minimalResultsHtml(), 200),
        ]);

        $exit = Artisan::call('guardian:results', ['tournamentId' => $tournament->id]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('already settled', Artisan::output());
        $this->assertSame('1:1', Event::query()->find(660002)->score);
    }

    public function test_fails_when_tournament_missing(): void
    {
        Http::fake();

        $exit = Artisan::call('guardian:results', ['tournamentId' => 99999999]);

        $this->assertSame(1, $exit);
        Http::assertNothingSent();
    }

    public function test_fails_when_guardian_results_url_missing(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'No URL League',
            'country' => 'England',
            'rank' => 1,
            'guardian_results_url' => null,
        ]);

        Http::fake();

        $exit = Artisan::call('guardian:results', ['tournamentId' => $tournament->id]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('guardian_results_url', Artisan::output());
        Http::assertNothingSent();
    }
}
