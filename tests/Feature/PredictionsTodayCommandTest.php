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

class PredictionsTodayCommandTest extends TestCase
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
    ): array {
        $tournament = Tournament::query()->firstOrCreate(['name' => 'Test League']);
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

    public function test_runs_predictions_for_each_unresolved_today_event(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', $tz));

        $this->seedExportableEvent(89001, 89011, Carbon::parse('2026-06-01 18:00:00', $tz));
        $this->seedExportableEvent(89002, 89012, Carbon::parse('2026-06-01 20:00:00', $tz));

        $this->seedExportableEvent(89003, 89013, Carbon::parse('2026-06-01 10:00:00', $tz), '1-0');
        $this->seedExportableEvent(89004, 89014, Carbon::now($tz)->subHour());

        $this->seedExportableEvent(89005, 89015, Carbon::parse('2026-06-02 18:00:00', $tz));

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::sequence()
                ->push([
                    'oddsId' => 89011,
                    'bankPercentage' => 3,
                    'explanation' => 'First pick.',
                ], 200)
                ->push([
                    'oddsId' => 89012,
                    'bankPercentage' => 5,
                    'explanation' => 'Second pick.',
                ], 200),
        ]);

        $exit = Artisan::call('predictions:today');

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 89001,
            'odds_id' => 89011,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 89002,
            'odds_id' => 89012,
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

        $exit = Artisan::call('predictions:today');

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('No unresolved', Artisan::output());
        Http::assertNothingSent();
    }
}
