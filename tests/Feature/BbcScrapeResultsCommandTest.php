<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BbcScrapeResultsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_settles_matching_event_from_fake_bbc_html(): void
    {
        $tournament = Tournament::query()->create([
            'id' => 1,
            'name' => 'Premier League',
            'country' => 'England',
            'bbc_results_url' => 'https://www.bbc.com/sport/football/premier-league/scores-fixtures',
        ]);

        $home = Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Alpha',
            'external_name' => 'Alpha FC',
            'short_name' => 'ALP',
            'league' => 'PL',
            'country' => 'England',
        ]);

        $away = Team::query()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Beta',
            'external_name' => 'Beta FC',
            'short_name' => 'BET',
            'league' => 'PL',
            'country' => 'England',
        ]);

        Event::query()->create([
            'id' => 99001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_SCHEDULED,
            'score' => null,
        ]);

        $yearMonth = now()->format('Y-m');
        $html = <<<'HTML'
<div data-event-id="s-test-alpha-beta" class="ssrcss-1bjtunb-GridContainer e1efi6g55">
<span aria-hidden="true" class="ssrcss-1p14tic-DesktopValue emlpoi30">Alpha FC</span>
<div class="ssrcss-qsbptj-HomeScore e56kr2l2">3</div>
<div class="ssrcss-fri5a2-AwayScore e56kr2l1">2</div>
<span aria-hidden="true" class="ssrcss-1p14tic-DesktopValue emlpoi30">Beta FC</span>
<div class="ssrcss-1739un0-StyledPeriod e307mhr0"><div>FT</div></div>
</div>
HTML;

        Http::fake([
            'https://www.bbc.com/sport/football/premier-league/scores-fixtures/'.$yearMonth.'*' => Http::response($html, 200),
        ]);

        $exit = Artisan::call('bbc:scrape-results', ['tournamentId' => $tournament->id]);

        $this->assertSame(0, $exit);

        $event = Event::query()->find(99001);
        $this->assertNotNull($event);
        $this->assertSame('3:2', $event->score);
        $this->assertSame(Event::STATUS_FINISHED, $event->status);
        $this->assertSame([], $event->additional_data ?? []);
    }

    public function test_strips_trailing_slash_from_bbc_results_url(): void
    {
        $tournament = Tournament::query()->create([
            'id' => 2,
            'name' => 'Allsvenskan',
            'country' => 'Sweden',
            'bbc_results_url' => 'https://www.bbc.com/sport/football/swedish-allsvenskan/scores-fixtures/',
        ]);

        $yearMonth = now()->format('Y-m');
        Http::fake([
            'https://www.bbc.com/sport/football/swedish-allsvenskan/scores-fixtures/'.$yearMonth.'*' => Http::response('"eventGroups":[]', 200),
        ]);

        $exit = Artisan::call('bbc:scrape-results', ['tournamentId' => $tournament->id]);

        $this->assertSame(0, $exit);
        Http::assertSent(fn ($request) => $request->url() === "https://www.bbc.com/sport/football/swedish-allsvenskan/scores-fixtures/{$yearMonth}");
    }

    public function test_warns_when_bbc_results_url_missing(): void
    {
        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'country' => 'England',
            'bbc_results_url' => null,
        ]);

        $exit = Artisan::call('bbc:scrape-results', ['tournamentId' => $tournament->id]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('has no bbc_results_url set', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_fails_when_tournament_not_found(): void
    {
        $exit = Artisan::call('bbc:scrape-results', ['tournamentId' => 999]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Tournament 999 not found', Artisan::output());
    }
}
