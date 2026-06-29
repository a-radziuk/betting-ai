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
use Tests\TestCase;

class PredictionsExportCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: int} event id, odd id
     */
    private function seedEventWithOdd(int $eventId): array
    {
        $tournament = Tournament::query()->create(['name' => 'Export League']);
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
            'status' => Event::STATUS_SCHEDULED,
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

    public function test_exports_active_predictions_to_dated_json_file(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-21 12:00:00', config('app.timezone')));

        [, $oddId1] = $this->seedEventWithOdd(930001);
        [, $oddId2] = $this->seedEventWithOdd(930002);

        EventPrediction::query()->create([
            'event_id' => 930001,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            'explanation' => 'Best pick.',
            'odds_id' => $oddId1,
            'bank_percentage' => 250,
            'confidence' => 8,
            'is_active' => true,
        ]);
        EventPrediction::query()->create([
            'event_id' => 930002,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT,
            'explanation' => 'Safest pick.',
            'odds_id' => $oddId2,
            'bank_percentage' => 50,
            'confidence' => null,
            'is_active' => true,
        ]);
        EventPrediction::query()->create([
            'event_id' => 930002,
            'prediction_type' => 'INACTIVE',
            'explanation' => 'Should not export.',
            'odds_id' => $oddId2,
            'bank_percentage' => 10,
            'is_active' => false,
        ]);

        $path = storage_path('app/predictions_export_2026-05-21.json');
        if (is_file($path)) {
            unlink($path);
        }

        $this->assertSame(0, Artisan::call('predictions:export'));

        $this->assertFileExists($path);
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(array_is_list($data));
        $this->assertCount(2, $data);

        $this->assertSame(EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT, $data[0]['type']);
        $this->assertSame('Best pick.', $data[0]['description']);
        $this->assertSame($oddId1, $data[0]['odd_id']);
        $this->assertSame(2500, $data[0]['stake']);
        $this->assertSame(8, $data[0]['confidence']);

        $this->assertSame(EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT, $data[1]['type']);
        $this->assertSame('Safest pick.', $data[1]['description']);
        $this->assertSame($oddId2, $data[1]['odd_id']);
        $this->assertSame(500, $data[1]['stake']);
        $this->assertArrayNotHasKey('confidence', $data[1]);

        @unlink($path);
        Carbon::setTestNow();
    }

    public function test_succeeds_with_empty_export_when_no_active_predictions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-22 12:00:00', config('app.timezone')));

        $path = storage_path('app/predictions_export_2026-05-22.json');
        if (is_file($path)) {
            unlink($path);
        }

        $exitCode = Artisan::call('predictions:export');
        $this->assertSame(0, $exitCode);
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $data);

        @unlink($path);
        Carbon::setTestNow();
    }

    public function test_exported_file_can_be_imported_via_upload_predictions_format(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-23 12:00:00', config('app.timezone')));

        [, $oddId] = $this->seedEventWithOdd(930010);

        EventPrediction::query()->create([
            'event_id' => 930010,
            'prediction_type' => 'CUSTOM_TYPE',
            'explanation' => 'Round trip.',
            'odds_id' => $oddId,
            'bank_percentage' => 100,
            'confidence' => 7,
            'is_active' => true,
        ]);

        $path = storage_path('app/predictions_export_2026-05-23.json');
        if (is_file($path)) {
            unlink($path);
        }

        $this->assertSame(0, Artisan::call('predictions:export'));
        EventPrediction::query()->delete();

        $exit = Artisan::call('predictions:import', ['filepath' => $path]);
        $this->assertSame(0, $exit);

        $prediction = EventPrediction::query()->first();
        $this->assertNotNull($prediction);
        $this->assertSame('CUSTOM_TYPE', $prediction->prediction_type);
        $this->assertSame('Round trip.', $prediction->explanation);
        $this->assertSame($oddId, $prediction->odds_id);
        $this->assertNull($prediction->bank_percentage);
        $this->assertSame('1000.00', (string) $prediction->stake);
        $this->assertSame(7, $prediction->confidence);
        $this->assertTrue($prediction->is_active);

        @unlink($path);
        Carbon::setTestNow();
    }
}
