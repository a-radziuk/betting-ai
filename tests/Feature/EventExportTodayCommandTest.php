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
            $this->seedExportableEvent(101004, 101014, 101024, 101034, $t1->id, $h1->id, $a1->id, Carbon::parse('2026-06-01 20:00:00', $tz));

            $exit = Artisan::call('event:export-today');
            $this->assertSame(0, $exit);
            $this->assertFileExists($out);

            $data = json_decode(file_get_contents($out), true);
            $this->assertIsArray($data);
            $this->assertCount(2, $data);
            $this->assertSame($t1->id, $data[0]['tournamentId']);
            $this->assertSame('League One', $data[0]['tournamentName']);
            $this->assertCount(2, $data[0]['events']);
            $this->assertSame('101001', $data[0]['events'][0]['eventId']);
            $this->assertSame('101004', $data[0]['events'][1]['eventId']);
            $this->assertSame($t2->id, $data[1]['tournamentId']);
            $this->assertSame('League Two', $data[1]['tournamentName']);
            $this->assertCount(1, $data[1]['events']);
            $this->assertSame('101002', $data[1]['events'][0]['eventId']);

            $this->assertFileDoesNotExist(storage_path('app/2026-06-01.txt'));
        } finally {
            Carbon::setTestNow();
            if (is_file($out)) {
                unlink($out);
            }
            $outTxt = storage_path('app/2026-06-01.txt');
            if (is_file($outTxt)) {
                unlink($outTxt);
            }
        }
    }

    public function test_exports_grouped_tournament_standings_at_tournament_level(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));
        $out = storage_path('app/2026-06-01.json');

        try {
            $tournament = Tournament::query()->create([
                'name' => 'World Cup',
                'standings' => [
                    'groups' => [
                        [
                            'name' => 'Group A',
                            'rows' => [
                                ['position' => 1, 'team' => 'Alpha FC', 'played' => 1, 'points' => 3],
                            ],
                        ],
                    ],
                ],
            ]);

            $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'W', 'tournament_id' => $tournament->id]);
            $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'W', 'tournament_id' => $tournament->id]);

            $this->seedExportableEvent(
                101501,
                101511,
                101521,
                101531,
                $tournament->id,
                $home->id,
                $away->id,
                Carbon::parse('2026-06-01 18:00:00', $tz),
            );

            $exit = Artisan::call('event:export-today');
            $this->assertSame(0, $exit);

            $data = json_decode(file_get_contents($out), true);
            $this->assertCount(1, $data);
            $this->assertIsArray($data[0]['standings']);
            $this->assertCount(1, $data[0]['standings']);
            $this->assertSame('Group A', $data[0]['standings'][0]['group']);
            $this->assertSame('Alpha FC', $data[0]['standings'][0]['team']);
            $this->assertArrayNotHasKey('standings', $data[0]['events'][0]);
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
            $this->assertSame($t1->id, $data[0]['tournamentId']);
            $this->assertSame('Only League', $data[0]['tournamentName']);
            $this->assertCount(1, $data[0]['events']);
            $this->assertSame('102001', $data[0]['events'][0]['eventId']);
        } finally {
            Carbon::setTestNow();
            if (is_file($out)) {
                unlink($out);
            }
            $outTxt = storage_path('app/2026-06-01.txt');
            if (is_file($outTxt)) {
                unlink($outTxt);
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
            $this->assertFileDoesNotExist(storage_path('app/2026-07-10.txt'));
        } finally {
            Carbon::setTestNow();
            if (is_file($out)) {
                unlink($out);
            }
            $outTxt = storage_path('app/2026-07-10.txt');
            if (is_file($outTxt)) {
                unlink($outTxt);
            }
        }
    }

    public function test_no_markets_option_is_passed_through_to_event_export(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00', $tz));
        $out = storage_path('app/2026-08-01.json');

        try {
            $t = Tournament::query()->create(['name' => 'Filter League']);
            $h = Team::query()->create(['name' => 'H3', 'short_name' => 'H3', 'league' => 'L', 'tournament_id' => $t->id]);
            $a = Team::query()->create(['name' => 'A3', 'short_name' => 'A3', 'league' => 'L', 'tournament_id' => $t->id]);

            $this->seedExportableEvent(103001, 103011, 103021, 103031, $t->id, $h->id, $a->id, Carbon::parse('2026-08-01 16:00:00', $tz));

            $hcMarket = Market::query()->create([
                'id' => 103040,
                'event_id' => 103001,
                'type' => Market::TYPE_HANDICAP,
                'period' => Market::PERIOD_FULL_TIME,
                'line' => null,
                'status' => Market::STATUS_OPEN,
                'is_supported_market' => true,
            ]);
            $hcSel = Selection::query()->create([
                'id' => 103041,
                'market_id' => $hcMarket->id,
                'name' => Selection::NAME_HOME,
                'participant_id' => null,
                'handicap' => -0.25,
                'created_at' => now(),
            ]);
            Odd::query()->create([
                'id' => 103042,
                'selection_id' => $hcSel->id,
                'odds' => 1.9,
                'probability' => null,
                'is_active' => true,
                'created_at' => now(),
            ]);

            $exit = Artisan::call('event:export-today', ['--no-markets' => 'HANDICAP']);
            $this->assertSame(0, $exit);
            $this->assertFileExists($out);

            $data = json_decode(file_get_contents($out), true);
            $this->assertCount(1, $data);
            $odds = $data[0]['events'][0]['odds'];
            $this->assertCount(1, $odds);
            $this->assertSame(Market::TYPE_MATCH_RESULT, $odds[0]['type']);
        } finally {
            Carbon::setTestNow();
            if (is_file($out)) {
                unlink($out);
            }
            $outTxt = storage_path('app/2026-08-01.txt');
            if (is_file($outTxt)) {
                unlink($outTxt);
            }
        }
    }

    public function test_full_writes_txt_with_same_json_and_instruction(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-09-15 10:00:00', $tz));
        $jsonPath = storage_path('app/2026-09-15.json');
        $txtPath = storage_path('app/2026-09-15.txt');

        try {
            $t = Tournament::query()->create(['name' => 'Full Day']);
            $h = Team::query()->create(['name' => 'HF', 'short_name' => 'HF', 'league' => 'L', 'tournament_id' => $t->id]);
            $a = Team::query()->create(['name' => 'AF', 'short_name' => 'AF', 'league' => 'L', 'tournament_id' => $t->id]);

            $this->seedExportableEvent(104001, 104011, 104021, 104031, $t->id, $h->id, $a->id, Carbon::parse('2026-09-15 14:00:00', $tz));

            $exit = Artisan::call('event:export-today', ['--full' => true]);
            $this->assertSame(0, $exit);
            $this->assertFileExists($jsonPath);
            $this->assertFileExists($txtPath);

            $json = file_get_contents($jsonPath);
            $txt = file_get_contents($txtPath);
            $this->assertStringStartsWith($json, $txt);
            $this->assertStringContainsString('Above is the odds for 1 games that are happening today.', $txt);
            $this->assertStringContainsString('1/ the safest bet', $txt);
            $this->assertStringContainsString('fifa_rank', $txt);
            $this->assertStringContainsString('fifa_points', $txt);
            $this->assertStringContainsString('odd_id: // id from the JSON', $txt);
            $this->assertStringContainsString('stake: // percent from 1000', $txt);
            $this->assertStringContainsString('description: // explain why you want to bet', $txt);
        } finally {
            Carbon::setTestNow();
            foreach ([$jsonPath, $txtPath] as $p) {
                if (is_file($p)) {
                    unlink($p);
                }
            }
        }
    }

    public function test_full_with_no_odds_writes_standings_focused_instruction(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-09-17 10:00:00', $tz));
        $jsonPath = storage_path('app/2026-09-17.json');
        $txtPath = storage_path('app/2026-09-17.txt');

        try {
            $t = Tournament::query()->create(['name' => 'Standings Day']);
            $h = Team::query()->create(['name' => 'HS', 'short_name' => 'HS', 'league' => 'L', 'tournament_id' => $t->id]);
            $a = Team::query()->create(['name' => 'AS', 'short_name' => 'AS', 'league' => 'L', 'tournament_id' => $t->id]);

            $this->seedExportableEvent(105001, 105011, 105021, 105031, $t->id, $h->id, $a->id, Carbon::parse('2026-09-17 15:00:00', $tz));

            $exit = Artisan::call('event:export-today', ['--full' => true, '--no-odds' => true]);
            $this->assertSame(0, $exit);
            $this->assertFileExists($txtPath);

            $txt = file_get_contents($txtPath);
            $this->assertStringContainsString('Above are 1 game happening today', $txt);
            $this->assertStringContainsString('most likely outcome', $txt);
            $this->assertStringContainsString('fifa_rank', $txt);
            $this->assertStringContainsString('fifa_points', $txt);
            $this->assertStringContainsString('Analyse the standings thoroughly', $txt);
            $this->assertStringContainsString('motivation', $txt);
            $this->assertStringNotContainsString('1/ the safest bet', $txt);
            $this->assertStringNotContainsString('odd_id: // id from the JSON', $txt);

            $data = json_decode(file_get_contents($jsonPath), true);
            $this->assertSame([], $data[0]['events'][0]['odds'] ?? null);
        } finally {
            Carbon::setTestNow();
            foreach ([$jsonPath, $txtPath] as $p) {
                if (is_file($p)) {
                    unlink($p);
                }
            }
        }
    }

    public function test_full_with_no_events_writes_txt_with_zero_games_instruction(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-09-16 11:00:00', $tz));
        $jsonPath = storage_path('app/2026-09-16.json');
        $txtPath = storage_path('app/2026-09-16.txt');

        try {
            $exit = Artisan::call('event:export-today', ['--full' => true]);
            $this->assertSame(0, $exit);
            $this->assertFileExists($jsonPath);
            $this->assertFileExists($txtPath);
            $this->assertSame('[]', trim(file_get_contents($jsonPath)));

            $txt = file_get_contents($txtPath);
            $this->assertStringContainsString('Above is the odds for 0 games that are happening today.', $txt);
        } finally {
            Carbon::setTestNow();
            foreach ([$jsonPath, $txtPath] as $p) {
                if (is_file($p)) {
                    unlink($p);
                }
            }
        }
    }

    public function test_full_with_no_events_and_no_odds_writes_standings_instruction(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-09-18 11:00:00', $tz));
        $txtPath = storage_path('app/2026-09-18.txt');

        try {
            $exit = Artisan::call('event:export-today', ['--full' => true, '--no-odds' => true]);
            $this->assertSame(0, $exit);
            $this->assertFileExists($txtPath);

            $txt = file_get_contents($txtPath);
            $this->assertStringContainsString('Above are 0 games happening today', $txt);
            $this->assertStringContainsString('most likely outcome', $txt);
            $this->assertStringNotContainsString('1/ the safest bet', $txt);
        } finally {
            Carbon::setTestNow();
            if (is_file($txtPath)) {
                unlink($txtPath);
            }
            $jsonPath = storage_path('app/2026-09-18.json');
            if (is_file($jsonPath)) {
                unlink($jsonPath);
            }
        }
    }
}
