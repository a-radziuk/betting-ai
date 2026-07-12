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

class EventExportAllForUploadCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_unresolved_events_to_dated_json_file(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19 12:00:00', config('app.timezone')));

        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        $scheduled = Event::query()->create([
            'id' => 88001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $finished = Event::query()->create([
            'id' => 88002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);
        $pastUnfinished = Event::query()->create([
            'id' => 88003,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subHour(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 88010,
            'event_id' => $scheduled->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 88011,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        Odd::query()->create([
            'id' => 88012,
            'selection_id' => $selection->id,
            'odds' => 2.15,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $path = storage_path('app/export_2026-05-19.json');
        if (is_file($path)) {
            unlink($path);
        }

        $this->assertSame(0, Artisan::call('event:export-all-for-upload'));

        $this->assertFileExists($path);
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('2026-05-19', $data['exportDate']);
        $this->assertCount(1, $data['events']);
        $this->assertSame(88001, $data['events'][0]['id']);
        $this->assertSame(Event::STATUS_SCHEDULED, $data['events'][0]['status']);
        $this->assertArrayNotHasKey('eventName', $data['events'][0]);
        $this->assertArrayNotHasKey('standings', $data['events'][0]);
        $this->assertCount(1, $data['events'][0]['markets']);
        $this->assertSame(Market::TYPE_MATCH_RESULT, $data['events'][0]['markets'][0]['type']);
        $this->assertSame(88010, $data['events'][0]['markets'][0]['id']);
        $this->assertCount(1, $data['events'][0]['markets'][0]['selections']);
        $this->assertSame(Selection::NAME_HOME, $data['events'][0]['markets'][0]['selections'][0]['name']);
        $this->assertCount(1, $data['events'][0]['markets'][0]['selections'][0]['odds']);
        $this->assertSame(88012, $data['events'][0]['markets'][0]['selections'][0]['odds'][0]['id']);
        $this->assertEquals(2.15, $data['events'][0]['markets'][0]['selections'][0]['odds'][0]['odds']);

        $eventIds = array_column($data['events'], 'id');
        $this->assertNotContains(88002, $eventIds);
        $this->assertNotContains(88003, $eventIds);

        unlink($path);
        Carbon::setTestNow();
    }

    public function test_exports_selection_value_in_upload_payload(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-22 12:00:00', config('app.timezone')));

        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 88201,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 88210,
            'event_id' => $event->id,
            'type' => Market::TYPE_TOTAL_ASIAN,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        Selection::query()->create([
            'id' => 88211,
            'market_id' => $market->id,
            'name' => Selection::NAME_OVER,
            'participant_id' => null,
            'handicap' => null,
            'value' => 2.5,
            'created_at' => now(),
        ]);

        $path = storage_path('app/export_2026-05-22.json');
        if (is_file($path)) {
            unlink($path);
        }

        $this->assertSame(0, Artisan::call('event:export-all-for-upload'));

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(2.5, $data['events'][0]['markets'][0]['selections'][0]['value']);

        unlink($path);
        Carbon::setTestNow();
    }

    public function test_succeeds_with_empty_export_when_no_unresolved_events(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 12:00:00', config('app.timezone')));

        $path = storage_path('app/export_2026-05-20.json');
        if (is_file($path)) {
            unlink($path);
        }

        $exitCode = Artisan::call('event:export-all-for-upload');
        $this->assertSame(0, $exitCode);
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $data['events']);

        unlink($path);
        Carbon::setTestNow();
    }

    public function test_exports_only_events_for_given_tournament(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-21 12:00:00', config('app.timezone')));

        $t1 = Tournament::query()->create(['name' => 'League One']);
        $t2 = Tournament::query()->create(['name' => 'League Two']);
        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        Event::query()->create([
            'id' => 88101,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $t1->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        Event::query()->create([
            'id' => 88102,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $t2->id,
            'start_time' => now()->addDays(2),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $path = storage_path('app/export_2026-05-21.json');
        if (is_file($path)) {
            unlink($path);
        }

        $exitCode = Artisan::call('event:export-all-for-upload', [
            'tournamentId' => (string) $t1->id,
        ]);
        $this->assertSame(0, $exitCode);
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $data['events']);
        $this->assertSame(88101, $data['events'][0]['id']);
        $this->assertSame($t1->id, $data['events'][0]['tournament_id']);

        unlink($path);
        Carbon::setTestNow();
    }

    public function test_fails_when_tournament_missing(): void
    {
        $exitCode = Artisan::call('event:export-all-for-upload', [
            'tournamentId' => '99999',
        ]);

        $this->assertSame(1, $exitCode);
    }
}
