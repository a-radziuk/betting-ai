<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\Team;
use App\Models\User;
use App\Models\UserBet;
use App\Support\PlayerResolvedBets;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerResolvedBetsCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_omits_admin_columns_for_non_superadmin(): void
    {
        $tz = config('app.timezone');
        $player = User::factory()->create();
        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $this->seedTwoResolvedBets($player, $tz);

        $content = $this->actingAs($viewer)
            ->get(route('players.bets.csv', $player))
            ->assertOk()
            ->streamedContent();

        $lines = array_values(array_filter(explode("\n", trim($content))));

        $this->assertSame(PlayerResolvedBets::csvHeaders(false), str_getcsv($lines[0]));
        $this->assertCount(8, str_getcsv($lines[1]));
        $this->assertSame('lost', str_getcsv($lines[1])[6]);
        $this->assertSame('-5.00', str_getcsv($lines[1])[7]);
    }

    public function test_download_includes_admin_columns_for_superadmin(): void
    {
        $tz = config('app.timezone');
        $player = User::factory()->create();
        $viewer = User::factory()->create([
            'is_superadmin' => true,
        ]);

        $this->seedTwoResolvedBets($player, $tz);

        $content = $this->actingAs($viewer)
            ->get(route('players.bets.csv', $player))
            ->assertOk()
            ->streamedContent();

        $lines = array_values(array_filter(explode("\n", trim($content))));

        $this->assertSame(PlayerResolvedBets::csvHeaders(true), str_getcsv($lines[0]));

        $lateRow = str_getcsv($lines[1]);
        $this->assertSame('2026-06-01 12:00', $lateRow[0]);
        $this->assertSame('Home FC — Away FC', $lateRow[1]);
        $this->assertSame('2-2', $lateRow[2]);
        $this->assertSame('lost', $lateRow[6]);
        $this->assertSame('-5.00', $lateRow[7]);
        $this->assertSame('5.00', $lateRow[8]);
        $this->assertSame('2', $lateRow[9]);

        $earlyRow = str_getcsv($lines[2]);
        $this->assertSame('2026-03-01 12:00', $earlyRow[0]);
        $this->assertSame('1-0', $earlyRow[2]);
        $this->assertSame('won', $earlyRow[6]);
        $this->assertSame('10.00', $earlyRow[7]);
        $this->assertSame('10.00', $earlyRow[8]);
        $this->assertSame('1', $earlyRow[9]);
    }

    private function seedTwoResolvedBets(User $player, string $tz): void
    {
        $home = Team::query()->create(['name' => 'Home FC', 'short_name' => 'HFC', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'Away FC', 'short_name' => 'AFC', 'league' => 'T']);

        $eventEarly = Event::query()->create([
            'id' => 93001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-03-01 12:00:00', $tz),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);
        $eventLate = Event::query()->create([
            'id' => 93002,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => Carbon::parse('2026-06-01 12:00:00', $tz),
            'status' => Event::STATUS_FINISHED,
            'score' => '2-2',
        ]);

        $this->seedBet($player, $eventEarly, 93010, 93020, 93030, UserBet::STATUS_WON, '10.00', '20.00', '10.00', 1);
        $this->seedBet($player, $eventLate, 93011, 93021, 93031, UserBet::STATUS_LOST, '5.00', '10.00', '5.00', 2);
    }

    public function test_guest_is_redirected_to_subscribe_page(): void
    {
        $player = User::factory()->create();

        $this->get(route('players.bets.csv', $player))
            ->assertRedirect(route('subscribe'));
    }

    public function test_user_without_see_tips_is_redirected_to_subscribe_page(): void
    {
        $player = User::factory()->create();
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('players.bets.csv', $player))
            ->assertRedirect(route('subscribe'));
    }

    public function test_player_page_shows_download_link_even_without_see_tips(): void
    {
        $player = User::factory()->create();
        $viewer = User::factory()->create();
        $this->seedResolvedBetForPlayer($player);

        $this->actingAs($viewer)
            ->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('Download CSV', false)
            ->assertSee(route('players.bets.csv', $player), false);
    }

    public function test_guest_sees_download_link_on_player_page(): void
    {
        $player = User::factory()->create();
        $this->seedResolvedBetForPlayer($player);

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('Download CSV', false)
            ->assertSee(route('players.bets.csv', $player), false);
    }

    public function test_player_page_hides_download_link_without_resolved_bets(): void
    {
        $player = User::factory()->create();
        $viewer = User::factory()->create([
            'priveleges' => User::PRIVELEGE_SEE_TIPS,
        ]);

        $html = $this->actingAs($viewer)
            ->get(route('players.show', $player))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Download CSV', $html);
    }

    private function seedResolvedBetForPlayer(User $player): void
    {
        $home = Team::query()->create(['name' => 'H', 'short_name' => 'H', 'league' => 'T']);
        $away = Team::query()->create(['name' => 'A', 'short_name' => 'A', 'league' => 'T']);
        $event = Event::query()->create([
            'id' => 93050,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now(),
            'status' => Event::STATUS_FINISHED,
            'score' => '0-0',
        ]);
        $this->seedBet($player, $event, 93051, 93052, 93053, UserBet::STATUS_WON, '10.00', '20.00');
    }

    private function seedBet(
        User $player,
        Event $event,
        int $marketId,
        int $selectionId,
        int $oddId,
        string $status,
        string $stake,
        string $potentialReturn,
        string $walletTotalResult = '0.00',
        int $resolvedOrder = 0,
    ): void {
        $market = Market::query()->create([
            'id' => $marketId,
            'event_id' => $event->id,
            'type' => Market::TYPE_MATCH_RESULT,
            'period' => Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = Selection::query()->create([
            'id' => $selectionId,
            'market_id' => $market->id,
            'name' => Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = Odd::query()->create([
            'id' => $oddId,
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
            'stake' => $stake,
            'odds_at_bet' => '2.0000',
            'potential_return' => $potentialReturn,
            'status' => $status,
            'wallet_total_result' => $walletTotalResult,
            'resolved_order' => $resolvedOrder,
        ]);
    }
}
