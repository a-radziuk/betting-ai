<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPrediction;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserPredictionSubscription;
use App\Models\UserWallet;
use App\Services\PlaceBetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PredictionsBetCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{event: Event, odd: Odd}
     */
    private function seedScheduledEventWithOdd(int $eventId = 77001, int $oddId = 77099): array
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

        $event = Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => 77002,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => 77003,
            'market_id' => $market->id,
            'name' => 'HOME',
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

    public function test_places_bet_for_subscriber_using_start_balance_percent(): void
    {
        ['event' => $event, 'odd' => $odd] = $this->seedScheduledEventWithOdd();

        EventPrediction::query()->create([
            'event_id' => $event->id,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            'odds_id' => $odd->id,
            'bank_percentage' => 10,
            'explanation' => 'Test pick.',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        UserPredictionSubscription::query()->create([
            'user_id' => $user->id,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
        ]);
        UserWallet::query()->where('user_id', $user->id)->update([
            'start_balance' => '200.00',
            'balance' => '500.00',
        ]);

        $exit = Artisan::call('predictions:bet');
        $this->assertSame(0, $exit);

        $bet = UserBet::query()->first();
        $this->assertNotNull($bet);
        $this->assertSame($user->id, $bet->user_id);
        $this->assertSame((int) $event->id, (int) $bet->event_id);
        $this->assertSame((string) $odd->id, (string) $bet->odd_id);
        $this->assertSame(EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT, $bet->prediction_type);
        $this->assertSame('20.00', (string) $bet->stake);

        $this->assertSame('480.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_skips_when_user_already_has_prediction_bet_for_event(): void
    {
        ['event' => $event, 'odd' => $odd] = $this->seedScheduledEventWithOdd();

        EventPrediction::query()->create([
            'event_id' => $event->id,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            'odds_id' => $odd->id,
            'bank_percentage' => 10,
            'explanation' => 'Test pick.',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        UserPredictionSubscription::query()->create([
            'user_id' => $user->id,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
        ]);
        UserWallet::query()->where('user_id', $user->id)->update([
            'start_balance' => '200.00',
            'balance' => '500.00',
        ]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => '5.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '10.00',
            'status' => UserBet::STATUS_PENDING,
            'prediction_type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
        ]);

        $exit = Artisan::call('predictions:bet');
        $this->assertSame(0, $exit);
        $this->assertSame(1, UserBet::query()->count());
        $this->assertStringContainsString('already has a bet', Artisan::output());
    }

    public function test_place_bet_service_accepts_prediction_type(): void
    {
        ['event' => $event, 'odd' => $odd] = $this->seedScheduledEventWithOdd(77010, 77098);

        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update([
            'balance' => '100.00',
            'start_balance' => '100.00',
        ]);

        $service = app(PlaceBetService::class);
        $result = $service->placeBet(
            $user->id,
            $odd->id,
            '10.00',
            EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT
        );

        $this->assertTrue($result['ok']);
        $bet = UserBet::query()->first();
        $this->assertSame(EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT, $bet->prediction_type);
    }

    public function test_reports_when_no_active_predictions(): void
    {
        $exit = Artisan::call('predictions:bet');
        $this->assertSame(0, $exit);
        $this->assertStringContainsString('No active', Artisan::output());
    }
}
