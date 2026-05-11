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
        Tournament::query()->create(['name' => 'Premier League']);

        $home = Team::query()->create([
            'tournament_id' => 1,
            'name' => 'Alpha',
            'external_name' => 'Alpha FC',
            'short_name' => 'ALP',
            'league' => 'PL',
            'country' => 'England',
        ]);

        $away = Team::query()->create([
            'tournament_id' => 1,
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
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_SCHEDULED,
            'score' => null,
        ]);

        $yearMonth = now()->format('Y-m');
        $html = <<<'HTML'
<script>
var x="prefix\",\"data\":{\"eventGroups\":[{\"secondaryGroups\":[{\"events\":[{\"home\":{\"fullName\":\"Alpha FC\",\"score\":\"3\"},\"away\":{\"fullName\":\"Beta FC\",\"score\":\"2\"},\"status\":\"PostEvent\"}]}]}]}";
</script>
HTML;

        Http::fake([
            'https://www.bbc.com/sport/football/premier-league/scores-fixtures/'.$yearMonth.'*' => Http::response($html, 200),
        ]);

        $exit = Artisan::call('bbc:scrape-results');

        $this->assertSame(0, $exit);

        $event = Event::query()->find(99001);
        $this->assertNotNull($event);
        $this->assertSame('3:2', $event->score);
        $this->assertSame(Event::STATUS_FINISHED, $event->status);
        $this->assertSame([], $event->additional_data ?? []);
    }
}
