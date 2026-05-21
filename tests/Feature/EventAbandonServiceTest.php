<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Services\EventAbandonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAbandonServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolved_order_increments_per_user(): void
    {
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        Event::query()->create([
            'id' => 98000,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subDays(2),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);
        $event = Event::query()->create([
            'id' => 98001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->subHours(3),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $user = User::factory()->create();
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => 98000,
            'odd_id' => 1,
            'stake' => 5,
            'odds_at_bet' => 2,
            'potential_return' => 10,
            'status' => UserBet::STATUS_WON,
            'resolved_order' => 3,
        ]);
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => 2,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        $service = app(EventAbandonService::class);
        $result = $service->abandon($event->id);

        $this->assertTrue($result['ok']);
        $this->assertSame(4, UserBet::query()->where('event_id', $event->id)->value('resolved_order'));
    }
}
