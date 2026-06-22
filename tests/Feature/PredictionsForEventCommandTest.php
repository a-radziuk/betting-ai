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

class PredictionsForEventCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedFutureEventWithOdds(int $eventId = 88001): void
    {
        $tournament = Tournament::query()->create(['name' => 'Premier League']);
        $home = Team::query()->create([
            'name' => 'Home FC',
            'short_name' => 'HOM',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'Away FC',
            'short_name' => 'AWY',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 88002,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => 88003,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        Odd::query()->create([
            'id' => 88004,
            'selection_id' => $selection->id,
            'odds' => 2.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);
    }

    public function test_calls_api_and_stores_prediction_for_future_event(): void
    {
        $this->seedFutureEventWithOdds();

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::response([
                'oddsId' => 1045886556460321359,
                'bankPercentage' => 3,
                'explanation' => 'Value on OVER 2.5.',
            ], 200),
        ]);

        $exit = Artisan::call('predictions:for-event', ['eventId' => 88001]);

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 88001,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            'odds_id' => 1045886556460321359,
            'bank_percentage' => 3,
            'explanation' => 'Value on OVER 2.5.',
            'confidence' => null,
            'is_active' => true,
        ]);

        $this->assertSame(1, EventPrediction::query()->active()->count());

        Http::assertSent(function ($request) {
            $data = $request->data();
            if (($data['type'] ?? null) !== EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT) {
                return false;
            }
            $event = $data['event'] ?? null;

            return is_array($event)
                && ($event['eventId'] ?? null) === '88001'
                && ($event['eventName'] ?? null) === 'Home FC vs Away FC';
        });
    }

    public function test_excludes_correct_score_markets_from_api_payload(): void
    {
        $this->seedFutureEventWithOdds();

        $correctScoreMarket = Market::query()->create([
            'id' => 88005,
            'event_id' => 88001,
            'type' => Market::TYPE_CORRECT_SCORE,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $correctScoreSelection = Selection::query()->create([
            'id' => 88006,
            'market_id' => $correctScoreMarket->id,
            'name' => '1-0',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        Odd::query()->create([
            'id' => 88007,
            'selection_id' => $correctScoreSelection->id,
            'odds' => 8.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::response([
                'oddsId' => 88004,
                'bankPercentage' => 3,
                'explanation' => 'Value on HOME.',
            ], 200),
        ]);

        $exit = Artisan::call('predictions:for-event', ['eventId' => 88001]);

        $this->assertSame(0, $exit);

        Http::assertSent(function ($request) {
            $odds = $request->data()['event']['odds'] ?? [];

            if (! is_array($odds)) {
                return false;
            }

            $types = array_column($odds, 'type');

            return ! in_array(Market::TYPE_CORRECT_SCORE, $types, true)
                && in_array(Market::TYPE_MATCH_RESULT, $types, true);
        });
    }

    public function test_stores_confidence_from_api_response(): void
    {
        $this->seedFutureEventWithOdds();

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::response([
                'oddsId' => 88004,
                'bankPercentage' => 4,
                'explanation' => 'Strong home form.',
                'confidence' => 8,
            ], 200),
        ]);

        $exit = Artisan::call('predictions:for-event', ['eventId' => 88001]);
        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 88001,
            'odds_id' => 88004,
            'confidence' => 8,
            'is_active' => true,
        ]);
    }

    public function test_deactivates_previous_prediction_when_running_again(): void
    {
        $this->seedFutureEventWithOdds();

        $first = EventPrediction::query()->create([
            'event_id' => 88001,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            'odds_id' => 111,
            'bank_percentage' => 1,
            'explanation' => 'Old pick.',
            'is_active' => true,
        ]);

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::response([
                'oddsId' => 222,
                'bankPercentage' => 5,
                'explanation' => 'New pick.',
            ], 200),
        ]);

        $exit = Artisan::call('predictions:for-event', ['eventId' => 88001]);
        $this->assertSame(0, $exit);

        $first->refresh();
        $this->assertFalse($first->is_active);

        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 88001,
            'odds_id' => 222,
            'bank_percentage' => 5,
            'explanation' => 'New pick.',
            'is_active' => true,
        ]);

        $this->assertSame(1, EventPrediction::query()->active()->count());
        $this->assertSame(2, EventPrediction::query()->count());
    }

    public function test_skips_past_event_without_calling_api(): void
    {
        $tournament = Tournament::query()->create(['name' => 'Premier League']);
        $home = Team::query()->create([
            'name' => 'H',
            'short_name' => 'H',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'A',
            'short_name' => 'A',
            'league' => 'England',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => 88010,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::now()->subDay(),
            'status' => Event::STATUS_FINISHED,
        ]);

        Http::fake();

        $exit = Artisan::call('predictions:for-event', ['eventId' => 88010]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('past', Artisan::output());
        $this->assertSame(0, EventPrediction::query()->count());
        Http::assertNothingSent();
    }

    public function test_fails_when_event_missing(): void
    {
        Http::fake();

        $exit = Artisan::call('predictions:for-event', ['eventId' => 99999999]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Event not found', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_uses_safest_prediction_type_when_passed_as_2(): void
    {
        $this->seedFutureEventWithOdds();

        Http::fake([
            'http://127.0.0.1:7999/api/odds' => Http::response([
                'oddsId' => 333,
                'bankPercentage' => 2,
                'explanation' => 'Safest line.',
            ], 200),
        ]);

        $exit = Artisan::call('predictions:for-event', [
            'eventId' => 88001,
            'predictionType' => 2,
        ]);

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas('event_predictions', [
            'event_id' => 88001,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT,
            'is_active' => true,
        ]);

        Http::assertSent(function ($request) {
            return ($request->data()['type'] ?? null) === EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT;
        });
    }

    public function test_fails_for_invalid_prediction_type(): void
    {
        $this->seedFutureEventWithOdds();

        Http::fake();

        $exit = Artisan::call('predictions:for-event', [
            'eventId' => 88001,
            'predictionType' => 4,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('predictionType must be 1, 2, or 3', Artisan::output());
        Http::assertNothingSent();
    }
}
