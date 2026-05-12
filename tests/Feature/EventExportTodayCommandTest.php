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

class EventExportTodayCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedExportableEvent(
        int $eventId,
        int $marketId,
        int $selectionId,
        int $oddId,
        int $tournamentId,
        int $homeId,
        int $awayId,
        Carbon $startTime,
    ): void {
        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'tournament_id' => $tournamentId,
            'start_time' => $startTime,
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $marketId,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $selectionId,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        Odd::query()->create([
            'id' => $oddId,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);
    }

    public function test_exports_todays_future_events_to_dated_json_file(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));
        $out = storage_path('app/2026-06-01.json');

        try {
            $t1 = Tournament::query()->create(['name' => 'League One']);
            $t2 = Tournament::query()->create(['name' => 'League Two']);

            $h1 = Team::query()->create(['name' => 'H1', 'short_name' => 'H1', 'league' => 'L', 'tournament_id' => $t1->id]);
            $a1 = Team::query()->create(['name' => 'A1', 'short_name' => 'A1', 'league' => 'L', 'tournament_id' => $t1->id]);
            $h2 = Team::query()->create(['name' => 'H2', 'short_name' => 'H2', 'league' => 'L', 'tournament_id' => $t2->id]);
            $a2 = Team::query()->create(['name' => 'A2', 'short_name' => 'A2', 'league' => 'L', 'tournament_id' => $t2->id]);

            $this->seedExportableEvent(101001, 101011, 101021, 101031, $t1->id, $h1->id, $a1->id, Carbon::parse('2026-06-01 18:00:00', $tz));
            $this->seedExportableEvent(101002, 101012, 101022, 101032, $t2->id, $h2->id, $a2->id, Carbon::parse('2026-06-01 19:00:00', $tz));
            $this->seedExportableEvent(101003, 101013, 101023, 101033, $t1->id, $h1->id, $a1->id, Carbon::parse('2026-06-01 08:00:00', $tz));

            $exit = Artisan::call('event:export-today');
            $this->assertSame(0, $exit);
            $this->assertFileExists($out);

            $data = json_decode(file_get_contents($out), true);
            $this->assertIsArray($data);
            $this->assertCount(2, $data);
            $this->assertSame('101001', $data[0]['eventId']);
            $this->assertSame('101002', $data[1]['eventId']);
        } finally {
            Carbon::setTestNow();
            if (is_file($out)) {
                unlink($out);
            }
        }
    }

    public function test_optional_tournament_id_limits_events(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));
        $out = storage_path('app/2026-06-01.json');

        try {
            $t1 = Tournament::query()->create(['name' => 'Only League']);
            $t2 = Tournament::query()->create(['name' => 'Other League']);

            $h1 = Team::query()->create(['name' => 'X1', 'short_name' => 'X1', 'league' => 'L', 'tournament_id' => $t1->id]);
            $a1 = Team::query()->create(['name' => 'Y1', 'short_name' => 'Y1', 'league' => 'L', 'tournament_id' => $t1->id]);
            $h2 = Team::query()->create(['name' => 'X2', 'short_name' => 'X2', 'league' => 'L', 'tournament_id' => $t2->id]);
            $a2 = Team::query()->create(['name' => 'Y2', 'short_name' => 'Y2', 'league' => 'L', 'tournament_id' => $t2->id]);

            $this->seedExportableEvent(102001, 102011, 102021, 102031, $t1->id, $h1->id, $a1->id, Carbon::parse('2026-06-01 18:00:00', $tz));
            $this->seedExportableEvent(102002, 102012, 102022, 102032, $t2->id, $h2->id, $a2->id, Carbon::parse('2026-06-01 19:00:00', $tz));

            $exit = Artisan::call('event:export-today', ['tournamentId' => (string) $t1->id]);
            $this->assertSame(0, $exit);

            $data = json_decode(file_get_contents($out), true);
            $this->assertCount(1, $data);
            $this->assertSame('102001', $data[0]['eventId']);
        } finally {
            Carbon::setTestNow();
            if (is_file($out)) {
                unlink($out);
            }
        }
    }

    public function test_fails_when_tournament_missing(): void
    {
        $exit = Artisan::call('event:export-today', ['tournamentId' => '99999']);
        $this->assertSame(1, $exit);
    }

    public function test_writes_empty_array_when_no_matches(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-07-10 12:00:00', $tz));
        $out = storage_path('app/2026-07-10.json');

        try {
            $exit = Artisan::call('event:export-today');
            $this->assertSame(0, $exit);
            $this->assertFileExists($out);
            $this->assertSame('[]', trim(file_get_contents($out)));
        } finally {
            Carbon::setTestNow();
            if (is_file($out)) {
                unlink($out);
            }
        }
    }
}
