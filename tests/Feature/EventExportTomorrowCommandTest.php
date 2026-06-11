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

class EventExportTomorrowCommandTest extends TestCase
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

    public function test_exports_tomorrows_future_events_to_dated_json_file(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));
        $out = storage_path('app/2026-06-02.json');

        try {
            $t1 = Tournament::query()->create(['name' => 'League One']);
            $t2 = Tournament::query()->create(['name' => 'League Two']);

            $h1 = Team::query()->create(['name' => 'H1', 'short_name' => 'H1', 'league' => 'L', 'tournament_id' => $t1->id]);
            $a1 = Team::query()->create(['name' => 'A1', 'short_name' => 'A1', 'league' => 'L', 'tournament_id' => $t1->id]);
            $h2 = Team::query()->create(['name' => 'H2', 'short_name' => 'H2', 'league' => 'L', 'tournament_id' => $t2->id]);
            $a2 = Team::query()->create(['name' => 'A2', 'short_name' => 'A2', 'league' => 'L', 'tournament_id' => $t2->id]);

            $this->seedExportableEvent(201001, 201011, 201021, 201031, $t1->id, $h1->id, $a1->id, Carbon::parse('2026-06-02 18:00:00', $tz));
            $this->seedExportableEvent(201002, 201012, 201022, 201032, $t2->id, $h2->id, $a2->id, Carbon::parse('2026-06-02 19:00:00', $tz));
            $this->seedExportableEvent(201003, 201013, 201023, 201033, $t1->id, $h1->id, $a1->id, Carbon::parse('2026-06-01 20:00:00', $tz));
            $this->seedExportableEvent(201004, 201014, 201024, 201034, $t1->id, $h1->id, $a1->id, Carbon::parse('2026-06-02 08:00:00', $tz));

            $exit = Artisan::call('event:export-tomorrow');
            $this->assertSame(0, $exit);
            $this->assertFileExists($out);

            $data = json_decode(file_get_contents($out), true);
            $this->assertIsArray($data);
            $this->assertCount(2, $data);
            $this->assertSame($t1->id, $data[0]['tournamentId']);
            $this->assertCount(2, $data[0]['events']);
            $this->assertSame('201004', $data[0]['events'][0]['eventId']);
            $this->assertSame('201001', $data[0]['events'][1]['eventId']);
            $this->assertSame($t2->id, $data[1]['tournamentId']);
            $this->assertCount(1, $data[1]['events']);
            $this->assertSame('201002', $data[1]['events'][0]['eventId']);

            $this->assertFileDoesNotExist(storage_path('app/2026-06-02.txt'));
        } finally {
            Carbon::setTestNow();
            if (is_file($out)) {
                unlink($out);
            }
            $outTxt = storage_path('app/2026-06-02.txt');
            if (is_file($outTxt)) {
                unlink($outTxt);
            }
        }
    }

    public function test_writes_empty_array_when_no_matches(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-07-10 12:00:00', $tz));
        $out = storage_path('app/2026-07-11.json');

        try {
            $exit = Artisan::call('event:export-tomorrow');
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

    public function test_full_writes_txt_with_same_json_and_tomorrow_instruction(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-09-15 10:00:00', $tz));
        $jsonPath = storage_path('app/2026-09-16.json');
        $txtPath = storage_path('app/2026-09-16.txt');

        try {
            $t = Tournament::query()->create(['name' => 'Full Day']);
            $h = Team::query()->create(['name' => 'HF', 'short_name' => 'HF', 'league' => 'L', 'tournament_id' => $t->id]);
            $a = Team::query()->create(['name' => 'AF', 'short_name' => 'AF', 'league' => 'L', 'tournament_id' => $t->id]);

            $this->seedExportableEvent(204001, 204011, 204021, 204031, $t->id, $h->id, $a->id, Carbon::parse('2026-09-16 14:00:00', $tz));

            $exit = Artisan::call('event:export-tomorrow', ['--full' => true]);
            $this->assertSame(0, $exit);
            $this->assertFileExists($jsonPath);
            $this->assertFileExists($txtPath);

            $json = file_get_contents($jsonPath);
            $txt = file_get_contents($txtPath);
            $this->assertStringStartsWith($json, $txt);
            $this->assertStringContainsString('Above is the odds for 1 games that are happening tomorrow.', $txt);
        } finally {
            Carbon::setTestNow();
            foreach ([$jsonPath, $txtPath] as $p) {
                if (is_file($p)) {
                    unlink($p);
                }
            }
        }
    }
}
