<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserWallet;
use App\Services\EventResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BetEventResultCommandTest extends TestCase
{
    use RefreshDatabase;

    private function seedMatchResultBet(int $eventId, string $selectionName, float $odds = 2.0): User
    {
        $home = Team::query()->create(['name' => 'Alpha', 'short_name' => 'ALP', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Beta', 'short_name' => 'BET', 'league' => 'T']);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $eventId * 100 + 1,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId * 100 + 2,
            'market_id' => $market->id,
            'name' => $selectionName,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $eventId * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => $odds,
            'probability' => 0.5,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 990]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => $odds,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        return $user;
    }

    public function test_settles_first_bet_with_resolved_order_one(): void
    {
        $this->seedMatchResultBet(88010, 'HOME', 2.0);

        Artisan::call('bet:event:result', [
            'event_id' => 88010,
            'result' => '2:0',
            'additional_data' => '{}',
        ]);

        $this->assertSame(1, UserBet::query()->value('resolved_order'));
    }

    public function test_settles_bet_with_resolved_order_after_previous_resolved_bet(): void
    {
        $user = $this->seedMatchResultBet(88011, 'HOME', 2.0);

        UserBet::query()->where('user_id', $user->id)->update([
            'status' => UserBet::STATUS_WON,
            'resolved_order' => 4,
        ]);

        $eventId = 88012;
        $home = Team::query()->firstOrFail();
        $away = Team::query()->skip(1)->firstOrFail();

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $eventId * 100 + 1,
            'event_id' => $eventId,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId * 100 + 2,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $eventId * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => 2.0,
            'probability' => 0.5,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $pendingBet = UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        Artisan::call('bet:event:result', [
            'event_id' => $eventId,
            'result' => '2:0',
            'additional_data' => '{}',
        ]);

        $this->assertSame(5, $pendingBet->fresh()->resolved_order);
    }

    public function test_settles_win_and_credits_wallet(): void
    {
        $user = $this->seedMatchResultBet(88001, 'HOME', 2.0);

        $exit = Artisan::call('bet:event:result', [
            'event_id' => 88001,
            'result' => '2:0',
            'additional_data' => '{"corners":"10:12"}',
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame('2:0', Event::query()->find(88001)->score);
        $this->assertSame(Event::STATUS_FINISHED, Event::query()->find(88001)->status);
        $this->assertSame('won', UserBet::query()->first()->status);
        $this->assertSame('1010.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_stores_optional_aet_and_penalty_scores(): void
    {
        $this->seedMatchResultBet(88020, 'HOME', 2.0);

        $exit = Artisan::call('bet:event:result', [
            'event_id' => 88020,
            'result' => '1:1',
            'additional_data' => '{}',
            '--score-aet' => '2:2',
            '--score-pen' => '5:4',
        ]);

        $this->assertSame(0, $exit);

        $event = Event::query()->find(88020);
        $this->assertSame('1:1', $event->score);
        $this->assertSame('2:2', $event->score_aet);
        $this->assertSame('5:4', $event->score_pen);
    }

    public function test_settles_loss_without_wallet_credit(): void
    {
        $user = $this->seedMatchResultBet(88002, 'HOME', 2.0);

        $exit = Artisan::call('bet:event:result', [
            'event_id' => 88002,
            'result' => '0:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame('lost', UserBet::query()->first()->status);
        $this->assertSame('990.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_draw_no_bet_refunds_on_draw(): void
    {
        $home = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'B', 'short_name' => 'B', 'league' => 'T']);
        $eid = 88003;

        Event::query()->create([
            'id' => $eid,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $eid * 100 + 1,
            'event_id' => $eid,
            'type' => Market::TYPE_DRAW_NO_BET,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eid * 100 + 2,
            'market_id' => $market->id,
            'name' => 'HOME',
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $eid * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => 1.5,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 990]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eid,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 1.5,
            'potential_return' => 15,
            'status' => UserBet::STATUS_PENDING,
        ]);

        Artisan::call('bet:event:result', [
            'event_id' => $eid,
            'result' => '1:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame('void', UserBet::query()->first()->status);
        $this->assertSame('1000.00', UserWallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_apply_event_result_twice_does_not_double_settle_bets(): void
    {
        $user = $this->seedMatchResultBet(88020, 'HOME', 2.0);
        $service = app(EventResultService::class);

        $first = $service->applyEventResult(88020, '2:0', []);
        $this->assertTrue($first['ok']);
        $this->assertSame('Event settled. Processed 1 pending bet(s).', $first['message']);

        $balanceAfterFirst = UserWallet::query()->where('user_id', $user->id)->value('balance');
        $betAfterFirst = UserBet::query()->first();
        $this->assertSame(UserBet::STATUS_WON, $betAfterFirst->status);
        $this->assertSame(1, $betAfterFirst->resolved_order);

        $second = $service->applyEventResult(88020, '2:0', []);
        $this->assertTrue($second['ok']);
        $this->assertSame('Event settled. Processed 0 pending bet(s).', $second['message']);
        $this->assertSame($balanceAfterFirst, UserWallet::query()->where('user_id', $user->id)->value('balance'));
        $this->assertSame(1, UserBet::query()->value('resolved_order'));
    }

    public function test_apply_event_result_skips_already_resolved_bets_on_same_event(): void
    {
        $userOne = $this->seedMatchResultBet(88021, 'HOME', 2.0);

        $eventId = 88021;
        $market = Market::query()->where('event_id', $eventId)->firstOrFail();
        $selection = Selection::query()->where('market_id', $market->id)->firstOrFail();
        $odd = Odd::query()->where('selection_id', $selection->id)->firstOrFail();

        $userTwo = User::factory()->create();
        UserWallet::query()->where('user_id', $userTwo->id)->update(['balance' => 990]);

        UserBet::query()->create([
            'user_id' => $userTwo->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'status' => UserBet::STATUS_WON,
            'resolved_order' => 3,
            'real_return' => 10,
            'wallet_total_result' => 10,
        ]);

        $pendingBet = UserBet::query()->where('user_id', $userOne->id)->where('event_id', $eventId)->firstOrFail();

        $service = app(EventResultService::class);
        $result = $service->applyEventResult($eventId, '2:0', []);

        $this->assertTrue($result['ok']);
        $this->assertSame('Event settled. Processed 1 pending bet(s).', $result['message']);
        $this->assertSame(UserBet::STATUS_WON, $pendingBet->fresh()->status);
        $this->assertSame(1, $pendingBet->fresh()->resolved_order);
        $this->assertSame(UserBet::STATUS_WON, UserBet::query()->where('user_id', $userTwo->id)->value('status'));
        $this->assertSame(3, UserBet::query()->where('user_id', $userTwo->id)->value('resolved_order'));
        $this->assertSame('1010.00', UserWallet::query()->where('user_id', $userOne->id)->value('balance'));
    }

    /**
     * @return array{user: User, event_id: int}
     */
    private function seedAsianMarketBet(
        int $eventId,
        string $marketType,
        string $selectionName,
        ?float $value = null,
    ): array {
        $home = Team::query()->create([
            'name' => 'Alpha '.$eventId,
            'short_name' => 'ALP',
            'league' => 'T',
        ]);
        $away = Team::query()->create([
            'name' => 'Beta '.$eventId,
            'short_name' => 'BET',
            'league' => 'T',
        ]);

        Event::query()->create([
            'id' => $eventId,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = Market::query()->create([
            'id' => $eventId * 100 + 1,
            'event_id' => $eventId,
            'type' => $marketType,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);

        $selection = Selection::query()->create([
            'id' => $eventId * 100 + 2,
            'market_id' => $market->id,
            'name' => $selectionName,
            'participant_id' => null,
            'handicap' => null,
            'value' => $value,
            'created_at' => now(),
        ]);

        $odd = Odd::query()->create([
            'id' => $eventId * 100 + 3,
            'selection_id' => $selection->id,
            'odds' => 2.0,
            'probability' => 0.5,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $user = User::factory()->create();
        UserWallet::query()->where('user_id', $user->id)->update(['balance' => 990]);

        UserBet::query()->create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'status' => UserBet::STATUS_PENDING,
        ]);

        return ['user' => $user, 'event_id' => $eventId];
    }

    public function test_total_asian_over_wins_and_under_loses(): void
    {
        $this->seedAsianMarketBet(88101, Market::TYPE_TOTAL_ASIAN, 'OVER', 2.5);
        $this->seedAsianMarketBet(88102, Market::TYPE_TOTAL_ASIAN, 'UNDER', 2.5);

        Artisan::call('bet:event:result', [
            'event_id' => 88101,
            'result' => '2:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_WON, UserBet::query()->where('event_id', 88101)->value('status'));

        Artisan::call('bet:event:result', [
            'event_id' => 88102,
            'result' => '2:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_LOST, UserBet::query()->where('event_id', 88102)->value('status'));
    }

    public function test_total_asian_refunds_when_total_equals_whole_line(): void
    {
        $this->seedAsianMarketBet(88103, Market::TYPE_TOTAL_ASIAN, 'OVER', 3.0);

        Artisan::call('bet:event:result', [
            'event_id' => 88103,
            'result' => '2:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_VOID, UserBet::query()->where('event_id', 88103)->value('status'));
    }

    public function test_home_total_asian_uses_home_goals_only(): void
    {
        $this->seedAsianMarketBet(88104, Market::TYPE_HOME_TOTAL_ASIAN, 'UNDER', 2.0);

        Artisan::call('bet:event:result', [
            'event_id' => 88104,
            'result' => '2:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_VOID, UserBet::query()->where('event_id', 88104)->value('status'));
    }

    public function test_away_total_asian_uses_away_goals_only(): void
    {
        $this->seedAsianMarketBet(88105, Market::TYPE_AWAY_TOTAL_ASIAN, 'OVER', 0.5);

        Artisan::call('bet:event:result', [
            'event_id' => 88105,
            'result' => '3:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_WON, UserBet::query()->where('event_id', 88105)->value('status'));
    }

    public function test_home_to_score_yes_wins_when_home_scores(): void
    {
        $this->seedAsianMarketBet(88106, Market::TYPE_HOME_TO_SCORE, 'YES');

        Artisan::call('bet:event:result', [
            'event_id' => 88106,
            'result' => '1:0',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_WON, UserBet::query()->where('event_id', 88106)->value('status'));
    }

    public function test_away_to_score_no_wins_when_away_does_not_score(): void
    {
        $this->seedAsianMarketBet(88107, Market::TYPE_AWAY_TO_SCORE, 'NO');

        Artisan::call('bet:event:result', [
            'event_id' => 88107,
            'result' => '2:0',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_WON, UserBet::query()->where('event_id', 88107)->value('status'));
    }

    public function test_handicap_asian_refunds_on_draw_after_handicap(): void
    {
        $this->seedAsianMarketBet(88108, Market::TYPE_HANDICAP_ASIAN, 'HOME', -1.0);

        Artisan::call('bet:event:result', [
            'event_id' => 88108,
            'result' => '2:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_VOID, UserBet::query()->where('event_id', 88108)->value('status'));
    }

    public function test_handicap_asian_away_wins_with_positive_handicap(): void
    {
        $this->seedAsianMarketBet(88109, Market::TYPE_HANDICAP_ASIAN, 'AWAY', 1.5);

        Artisan::call('bet:event:result', [
            'event_id' => 88109,
            'result' => '2:1',
            'additional_data' => '{}',
        ]);

        $this->assertSame(UserBet::STATUS_WON, UserBet::query()->where('event_id', 88109)->value('status'));
    }
}
