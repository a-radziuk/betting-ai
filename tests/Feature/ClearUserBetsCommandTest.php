<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ClearUserBetsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedBetForUser(User $user, int $eventId): void
    {
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $market = Market::query()->create([
            'id' => $eventId * 10 + 1,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => $eventId * 10 + 2,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => $eventId * 10 + 3,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);
        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);
    }

    public function test_clears_all_user_bets_when_no_user_argument(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->seedBetForUser($u1, 501);
        $this->seedBetForUser($u2, 502);

        $exit = Artisan::call('bets:clear-user-bets');

        $this->assertSame(0, $exit);
        $this->assertSame(0, UserBet::query()->count());
    }

    public function test_clears_bets_only_for_given_user(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->seedBetForUser($u1, 601);
        $this->seedBetForUser($u2, 602);

        $exit = Artisan::call('bets:clear-user-bets', ['userId' => $u1->id]);

        $this->assertSame(0, $exit);
        $this->assertSame(1, UserBet::query()->count());
        $this->assertSame($u2->id, (int) UserBet::query()->value('user_id'));
    }
}
