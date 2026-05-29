<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserBet;
use App\Services\PlayerShowCache;
use App\Services\PlayersIndexCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlayersPageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'page_cache.cache_enabled' => true,
            'page_cache.cache_store' => 'array',
            'page_cache.cache_ttl' => 300,
        ]);
    }

    public function test_players_index_main_content_is_stored_in_cache(): void
    {
        Cache::store('array')->flush();

        $cache = app(PlayersIndexCache::class);

        $this->get(route('players.index'))->assertOk();

        $this->assertTrue(Cache::store('array')->has($cache->cacheKey(1)));
    }

    public function test_player_show_main_content_is_stored_in_cache(): void
    {
        Cache::store('array')->flush();

        $player = User::factory()->create();
        $cache = app(PlayerShowCache::class);

        $this->get(route('players.show', $player))->assertOk();

        $this->assertTrue(Cache::store('array')->has($cache->cacheKey($player, 1)));
    }

    public function test_player_show_serves_cached_main_without_hitting_database_on_second_request(): void
    {
        Cache::store('array')->flush();

        $player = User::factory()->create();
        $tournament = Tournament::query()->create(['name' => 'League', 'rank' => 1]);
        $home = Team::query()->create(['name' => 'Cached Home', 'short_name' => 'CH', 'league' => 'L']);
        $away = Team::query()->create(['name' => 'Cached Away', 'short_name' => 'CA', 'league' => 'L']);
        Event::query()->create([
            'id' => 99201,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_FINISHED,
            'score' => '1-0',
        ]);

        $marketId = 99210;
        $selectionId = 99220;
        $oddId = 99230;
        $market = \App\Models\Market::query()->create([
            'id' => $marketId,
            'event_id' => 99201,
            'type' => \App\Models\Market::TYPE_MATCH_RESULT,
            'period' => \App\Models\Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => \App\Models\Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = \App\Models\Selection::query()->create([
            'id' => $selectionId,
            'market_id' => $market->id,
            'name' => \App\Models\Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        \App\Models\Odd::query()->create([
            'id' => $oddId,
            'selection_id' => $selection->id,
            'odds' => 2,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);
        UserBet::query()->create([
            'user_id' => $player->id,
            'event_id' => 99201,
            'odd_id' => $oddId,
            'stake' => '10.00',
            'odds_at_bet' => '2.0000',
            'potential_return' => '20.00',
            'status' => UserBet::STATUS_WON,
            'wallet_total_result' => '10.00',
            'resolved_order' => 1,
        ]);

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('Cached Home', false);

        $home->update(['name' => 'Changed Home Name']);

        $this->get(route('players.show', $player))
            ->assertOk()
            ->assertSee('Cached Home', false)
            ->assertDontSee('Changed Home Name', false);
    }

    public function test_header_is_not_cached_on_players_pages(): void
    {
        Cache::store('array')->flush();

        $user = User::factory()->create();

        $this->get(route('players.index'))
            ->assertOk()
            ->assertSee(__('Login'), false);

        $this->actingAs($user)
            ->get(route('players.index'))
            ->assertOk()
            ->assertSee(__('Log out'), false);
    }
}
