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
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PredictionsTomorrowCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{event: Event, odd: Odd}
     */
    private function seedExportableEvent(
        int $eventId,
        int $oddId,
        Carbon $startTime,
        ?string $score = null,
        ?int $tournamentId = null,
    ): array {
        $tournament = $tournamentId !== null
            ? Tournament::query()->findOrFail($tournamentId)
            : Tournament::query()->firstOrCreate(['name' => 'Test League']);
        $home = Team::query()->create([
            'name' => "Home {$eventId}",
            'short_name' => 'H'.$eventId,
            'league' => 'Test',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => "Away {$eventId}",
            'short_name' => 'A'.$eventId,
            'league' => 'Test',
            'tournament_id' => $tournament->id,
        ]);

        $event = Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => $startTime,
            'status' => Event::STATUS_SCHEDULED,
            'score' => $score,
        ]);

        $market = Market::query()->create([
            'id' => $eventId + 1000,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId + 2000,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $oddId,
            'selection_id' => $selection->id,
            'odds' => 2.0,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        return ['event' => $event, 'odd' => $odd];
    }

    public function test_runs_predictions_for_each_unresolved_tomorrow_event(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));

        $this->seedExportableEvent(89101, 89111, Carbon::parse('2026-06-02 18:00:00', $tz));
        $this->seedExportableEvent(89102, 89112, Carbon::parse('2026-06-02 20:00:00', $tz));

        $this->seedExportableEvent(89103, 89113, Carbon::parse('2026-06-02 10:00:00', $tz), '1-0');
        $this->seedExportableEvent(89104, 89114, Carbon::parse('2026-06-01 18:00:00', $tz));

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::sequence()
                ->push([
                    'oddsId' => 89111,
                    'bankPercentage' => 3,
                    'explanation' => 'First pick.',
                ], 200)
                ->push([
                    'oddsId' => 89112,
                    'bankPercentage' => 5,
                    'explanation' => 'Second pick.',
                ], 200),
        ]);

        $exit = Artisan::call('predictions:tomorrow');

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 89101,
            'odds_id' => 89111,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 89102,
            'odds_id' => 89112,
            'is_active' => true,
        ]);
        $this->assertSame(2, EventPrediction::query()->active()->count());
        Http::assertSentCount(2);
    }

    public function test_reports_when_no_matching_events(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));

        Http::fake();

        $exit = Artisan::call('predictions:tomorrow');

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('No unresolved', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_passes_prediction_type_to_for_event_command(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));

        $this->seedExportableEvent(89110, 89120, Carbon::parse('2026-06-02 18:00:00', $tz));

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::response([
                'oddsId' => 89120,
                'bankPercentage' => 4,
                'explanation' => 'Upset pick.',
            ], 200),
        ]);

        $exit = Artisan::call('predictions:tomorrow', ['predictionType' => 3]);

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 89110,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_UPSET_FOR_EVENT_DEFAULT,
            'is_active' => true,
        ]);

        Http::assertSent(function ($request) {
            return ($request->data()['type'] ?? null) === EventPrediction::PREDICTION_TYPE_GET_ONE_UPSET_FOR_EVENT_DEFAULT;
        });
    }

    public function test_fails_for_invalid_prediction_type(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));

        $this->seedExportableEvent(89110, 89120, Carbon::parse('2026-06-02 18:00:00', $tz));

        Http::fake();

        $exit = Artisan::call('predictions:tomorrow', ['predictionType' => 9]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('predictionType must be 1, 2, or 3', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_runs_predictions_only_for_given_tournament(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));

        $t1 = Tournament::query()->create(['name' => 'League One']);
        $t2 = Tournament::query()->create(['name' => 'League Two']);

        $this->seedExportableEvent(89121, 89131, Carbon::parse('2026-06-02 18:00:00', $tz), null, $t1->id);
        $this->seedExportableEvent(89122, 89132, Carbon::parse('2026-06-02 20:00:00', $tz), null, $t2->id);

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::response([
                'oddsId' => 89131,
                'bankPercentage' => 3,
                'explanation' => 'Tournament one pick.',
            ], 200),
        ]);

        $exit = Artisan::call('predictions:tomorrow', [
            'tournamentId' => (string) $t1->id,
        ]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 89121,
            'odds_id' => 89131,
            'is_active' => true,
        ]);
        $this->assertDatabaseMissing('event_predictions', [
            'event_id' => 89122,
        ]);
        Http::assertSentCount(1);
    }

    public function test_fails_when_tournament_missing(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));

        Http::fake();

        $exit = Artisan::call('predictions:tomorrow', [
            'tournamentId' => '99999',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Tournament [99999] not found', Artisan::output());
        Http::assertNothingSent();
    }
}
