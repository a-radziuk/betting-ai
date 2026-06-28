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

class PredictionsListCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: int} event id, odd id
     */
    private function seedEventWithOdd(int $eventId): array
    {
        $tournament = Tournament::query()->create(['name' => 'List League']);
        $home = Team::query()->create([
            'name' => 'Home FC',
            'short_name' => 'HOM',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);
        $away = Team::query()->create([
            'name' => 'Away FC',
            'short_name' => 'AWY',
            'league' => 'L',
            'tournament_id' => $tournament->id,
        ]);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => Carbon::parse('2026-06-28 15:00:00'),
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

    public function test_lists_active_predictions_in_table(): void
    {
        [, $oddId1] = $this->seedEventWithOdd(940001);
        [, $oddId2] = $this->seedEventWithOdd(940002);

        EventPrediction::query()->create([
            'event_id' => 940001,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            'explanation' => 'Best pick for the match.',
            'odds_id' => $oddId1,
            'bank_percentage' => 10,
            'confidence' => 8,
            'is_active' => true,
        ]);
        EventPrediction::query()->create([
            'event_id' => 940002,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT,
            'explanation' => 'Safest pick.',
            'odds_id' => $oddId2,
            'bank_percentage' => 5,
            'confidence' => null,
            'is_active' => true,
        ]);
        EventPrediction::query()->create([
            'event_id' => 940002,
            'prediction_type' => 'INACTIVE',
            'explanation' => 'Should not list.',
            'odds_id' => $oddId2,
            'bank_percentage' => 10,
            'is_active' => false,
        ]);

        $exitCode = Artisan::call('predictions:list');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('940001', $output);
        $this->assertStringContainsString('940002', $output);
        $this->assertStringContainsString('HOM vs AWY', $output);
        $this->assertStringContainsString('GET_ONE_BEST_FOR_EVENT_DEFAULT', $output);
        $this->assertStringContainsString('GET_ONE_SAFEST_FOR_EVENT_DEFAULT', $output);
        $this->assertStringContainsString('Best pick for the match.', $output);
        $this->assertStringContainsString('Showing 2 active prediction(s).', $output);
        $this->assertStringNotContainsString('Should not list.', $output);
    }

    public function test_reports_when_no_active_predictions(): void
    {
        $exitCode = Artisan::call('predictions:list');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No active event predictions found.', Artisan::output());
    }
}
