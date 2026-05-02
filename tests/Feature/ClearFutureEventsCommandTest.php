<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ClearFutureEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_clears_score_and_schedules_future_events_only(): void
    {
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);

        Event::query()->create([
            'id' => 401,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDays(2),
            'status' => Event::STATUS_FINISHED,
            'score' => '3:1',
        ]);

        Event::query()->create([
            'id' => 402,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '0:0',
        ]);

        $exit = Artisan::call('bets:clear-events');

        $this->assertSame(0, $exit);

        $future = Event::query()->find(401);
        $this->assertNull($future->score);
        $this->assertSame(Event::STATUS_SCHEDULED, $future->status);

        $past = Event::query()->find(402);
        $this->assertSame('0:0', $past->score);
        $this->assertSame(Event::STATUS_FINISHED, $past->status);
    }
}
