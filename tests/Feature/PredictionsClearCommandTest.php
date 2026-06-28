<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPrediction;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PredictionsClearCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_all_active_predictions(): void
    {
        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'HOM', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'AWY', 'league' => 'T']);

        $event = Event::query()->create([
            'id' => 950001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        EventPrediction::query()->create([
            'event_id' => $event->id,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            'odds_id' => 950010,
            'bank_percentage' => 10,
            'explanation' => 'Active pick.',
            'is_active' => true,
        ]);
        EventPrediction::query()->create([
            'event_id' => $event->id,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT,
            'odds_id' => 950011,
            'bank_percentage' => 5,
            'explanation' => 'Another active pick.',
            'is_active' => true,
        ]);
        EventPrediction::query()->create([
            'event_id' => $event->id,
            'prediction_type' => 'INACTIVE',
            'odds_id' => 950012,
            'bank_percentage' => 5,
            'explanation' => 'Inactive pick.',
            'is_active' => false,
        ]);

        $exitCode = Artisan::call('predictions:clear');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, EventPrediction::query()->active()->count());
        $this->assertSame(1, EventPrediction::query()->count());
        $this->assertStringContainsString('Deleted 2 active prediction(s).', $output);
    }

    public function test_reports_when_no_active_predictions(): void
    {
        $exitCode = Artisan::call('predictions:clear');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No active event predictions found.', Artisan::output());
    }
}
