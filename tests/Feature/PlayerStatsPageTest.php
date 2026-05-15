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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlayerStatsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolved_bets_sorted_by_event_start_time_desc(): void
    {
        $tz = config('app.timezone');
        $player = User::factory()->create();

        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);

        $eventEarly = Event::query()->create([
            'id' => 92001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-03-01 12:00:00', $tz),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);
        $eventLate = Event::query()->create([
            'id' => 92002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-06-01 12:00:00', $tz),
            'status' => Event::STATUS_FINISHED,
            'score' => '2-2',
        ]);

        $market = Market::query()->create([
            'id' => 92010,
            'event_id' => $eventEarly->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 92020,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $oddEarly = Odd::query()->create([
            'id' => 92030,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $marketLate = Market::query()->create([
            'id' => 92011,
            'event_id' => $eventLate->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selectionLate = Selection::query()->create([
            'id' => 92021,
            'market_id' => $marketLate->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $oddLate = Odd::query()->create([
            'id' => 92031,
            'selection_id' => $selectionLate->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $eventEarly->id,
            'odd_id' => $oddEarly->id,
            'stake' => '10.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '20.00',
            'status' => UserBet::STATUS_WON,
            'wallet_total_result' => '10.00',
        ]);
        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $eventLate->id,
            'odd_id' => $oddLate->id,
            'stake' => '5.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '10.00',
            'status' => UserBet::STATUS_LOST,
            'wallet_total_result' => '5.00',
        ]);

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->assertDontSee('<th>Resolved</th>', false)
            ->getContent();

        $posLate = strpos($html, '2026-06-01 12:00');
        $posEarly = strpos($html, '2026-03-01 12:00');
        $this->assertNotFalse($posLate);
        $this->assertNotFalse($posEarly);
        $this->assertLessThan($posEarly, $posLate);
    }

    public function test_shows_wallet_result_chart_instead_of_balance(): void
    {
        $tz = config('app.timezone');
        $player = User::factory()->create();
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $event = Event::query()->create([
            'id' => 92050,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-05-01 12:00:00', $tz),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);
        $market = Market::query()->create([
            'id' => 92051,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 92052,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => 92059,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => '10.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '20.00',
            'status' => UserBet::STATUS_WON,
            'wallet_total_result' => '15.50',
        ]);

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('user-results-label">Balance</span>', $html);
        $this->assertStringContainsString('user-results-item--chart', $html);
        $this->assertStringContainsString('user-results-chart', $html);
        $this->assertStringContainsString('+15.50', $html);
        $this->assertStringContainsString('points="', $html);
        $this->assertStringContainsString('user-results-chart-dot', $html);
        $this->assertStringContainsString('user-results-chart-dot--origin', $html);
        $this->assertStringContainsString('user-results-chart-tooltip', $html);
        $this->assertStringContainsString('>0.00</text>', $html);
    }

    public function test_currently_in_play_box_shows_pending_bet_count(): void
    {
        $tz = config('app.timezone');
        $player = User::factory()->create();
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $event = Event::query()->create([
            'id' => 92070,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-06-01 12:00:00', $tz),
            'status' => Event::STATUS_SCHEDULED,
        ]);
        $market = Market::query()->create([
            'id' => 92071,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 92072,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => 92073,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        foreach ([10.00, 5.00] as $stake) {
            UserBet::query()->create([
                'user_id' => $player->id,
                'event_id' => $event->id,
                'odd_id' => $odd->id,
                'stake' => (string) $stake,
                'odds_at_bet' => '2.0000',
                'potential_return' => number_format($stake * 2, 2, '.', ''),
                'status' => UserBet::STATUS_PENDING,
            ]);
        }

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('user-results-in-play-meta', $html);
        $this->assertStringContainsString('2 bets', $html);
    }

    public function test_result_box_shows_bet_count_turnover_result_and_efficiency(): void
    {
        $tz = config('app.timezone');
        $player = User::factory()->create();
        UserWallet::query()->where('user_id', $player->id)->update(['total_result' => '10.00']);

        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $event = Event::query()->create([
            'id' => 92060,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-05-01 12:00:00', $tz),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);
        $market = Market::query()->create([
            'id' => 92061,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => 92062,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => 92063,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => '10.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '20.00',
            'status' => UserBet::STATUS_WON,
            'wallet_total_result' => '10.00',
        ]);
        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => '5.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '10.00',
            'status' => UserBet::STATUS_LOST,
            'wallet_total_result' => '5.00',
        ]);

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('user-results-item--metrics', $html);
        $this->assertStringContainsString('metric-info', $html);
        $this->assertStringContainsString('Number of settled bets', $html);
        $this->assertStringContainsString('Total stake staked', $html);
        $this->assertStringContainsString('Average stake', $html);
        $this->assertStringContainsString('7.50', $html);
        $this->assertStringContainsString('Relative Efficiency', $html);
        $this->assertStringContainsString('+10.00', $html);
        $this->assertStringContainsString('+66.7%', $html);
    }

    public function test_displays_extended_profile_fields_when_set(): void
    {
        Storage::fake('public');

        $player = User::factory()->create([
            'tagline' => 'EPL value hunter',
            'bio' => 'Focus on match odds.',
            'city' => 'London',
            'country' => 'United Kingdom',
        ]);

        $path = UploadedFile::fake()->image('avatar.jpg')->store('avatars', 'public');
        $player->update(['avatar' => $path]);

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="card card-pad player-profile"', $html);
        $this->assertStringContainsString('EPL value hunter', $html);
        $this->assertStringContainsString('Focus on match odds.', $html);
        $this->assertStringContainsString('London', $html);
        $this->assertStringContainsString('United Kingdom', $html);
        $this->assertStringContainsString(Storage::disk('public')->url($path), $html);
    }

    public function test_hides_extended_profile_rows_when_empty(): void
    {
        $player = User::factory()->create([
            'tagline' => null,
            'bio' => null,
            'city' => null,
            'country' => null,
            'avatar' => null,
        ]);

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('class="card card-pad player-profile"', $html);
        $this->assertStringNotContainsString('class="player-profile-label">Tagline</dt>', $html);
        $this->assertStringNotContainsString('class="player-profile-label">Bio</dt>', $html);
        $this->assertStringNotContainsString('class="player-profile-label">City</dt>', $html);
        $this->assertStringNotContainsString('class="player-profile-label">Country</dt>', $html);
        $this->assertStringNotContainsString('class="player-profile-label">Photo</dt>', $html);
    }

    public function test_shows_only_filled_profile_rows(): void
    {
        $player = User::factory()->create([
            'tagline' => 'Only tagline',
            'bio' => null,
            'city' => 'Berlin',
            'country' => null,
        ]);

        $html = $this->get(route('players.show', $player))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Only tagline', $html);
        $this->assertStringContainsString('Berlin', $html);
        $this->assertStringContainsString('class="card card-pad player-profile"', $html);
        $this->assertStringNotContainsString('class="player-profile-label">Bio</dt>', $html);
        $this->assertStringNotContainsString('class="player-profile-label">Country</dt>', $html);
        $this->assertStringNotContainsString('class="player-profile-label">Photo</dt>', $html);
    }
}
