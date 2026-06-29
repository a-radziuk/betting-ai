<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPrediction;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use InvalidArgumentException;
use Tests\TestCase;

class PredictionsImportCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: int} event id, odd id
     */
    private function seedEventWithOdd(int $eventId, string $status = Event::STATUS_SCHEDULED): array
    {
        $tournament = Tournament::query()->create(['name' => 'Import League']);
        $home = Team::query()->create([
            'name' => 'H',
            'short_name' => 'H',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A',
            'short_name' => 'A',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::now()->addDay(),
            'status' => $status,
        ]);

        $market = Market::query()->create([
            'id' => $eventId + 1,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId + 2,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $oddId = $eventId + 3;
        Odd::query()->create([
            'id' => $oddId,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return [$eventId, $oddId];
    }

    public function test_imports_predictions_from_json_file(): void
    {
        [, $oddId] = $this->seedEventWithOdd(910001);

        $path = sys_get_temp_dir().'/predictions-import-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            [
                'type' => 'CUSTOM_TYPE',
                'description' => 'Because form.',
                'odd_id' => $oddId,
                'stake' => 2500,
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('predictions:import', ['filepath' => $path]);
            $this->assertSame(0, $exit);

            $p = EventPrediction::query()->first();
            $this->assertNotNull($p);
            $this->assertSame('CUSTOM_TYPE', $p->prediction_type);
            $this->assertSame('Because form.', $p->explanation);
            $this->assertSame($oddId, $p->odds_id);
            $this->assertSame(910001, $p->event_id);
            $this->assertNull($p->bank_percentage);
            $this->assertSame('2500.00', (string) $p->stake);
            $this->assertTrue($p->is_active);
            $this->assertNull($p->confidence);
        } finally {
            @unlink($path);
        }
    }

    public function test_imports_confidence_when_present(): void
    {
        [, $oddId] = $this->seedEventWithOdd(910002);

        $path = sys_get_temp_dir().'/predictions-import-confidence-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            [
                'type' => 'CUSTOM_TYPE',
                'description' => 'High conviction.',
                'odd_id' => $oddId,
                'stake' => 1000,
                'confidence' => 85,
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('predictions:import', ['filepath' => $path]);
            $this->assertSame(0, $exit);

            $p = EventPrediction::query()->first();
            $this->assertNotNull($p);
            $this->assertSame(85, $p->confidence);
        } finally {
            @unlink($path);
        }
    }

    public function test_skips_finished_events(): void
    {
        [, $oddId] = $this->seedEventWithOdd(910101, Event::STATUS_FINISHED);

        $path = sys_get_temp_dir().'/predictions-import-skip-'.uniqid('', true).'.json';
        file_put_contents($path, json_encode([
            [
                'type' => 'X',
                'description' => 'Y',
                'odd_id' => $oddId,
                'stake' => 1000,
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $exit = Artisan::call('predictions:import', ['filepath' => $path]);
            $this->assertSame(0, $exit);
            $this->assertSame(0, EventPrediction::query()->count());
        } finally {
            @unlink($path);
        }
    }

    public function test_throws_when_file_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found');

        Artisan::call('predictions:import', ['filepath' => '/this/path/does/not/exist-'.uniqid().'.json']);
    }

    public function test_throws_when_json_is_not_a_list(): void
    {
        $path = sys_get_temp_dir().'/predictions-import-obj-'.uniqid('', true).'.json';
        file_put_contents($path, '{"a":1}');

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('list');

            Artisan::call('predictions:import', ['filepath' => $path]);
        } finally {
            @unlink($path);
        }
    }
}
