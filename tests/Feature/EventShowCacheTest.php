<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\EventShowCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EventShowCacheTest extends TestCase
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

    public function test_event_main_content_is_stored_in_cache(): void
    {
        Cache::store('array')->flush();

        $home = Team::query()->create(['name' => 'Cache Home', 'short_name' => 'CH', 'league' => 'L']);
        $away = Team::query()->create(['name' => 'Cache Away', 'short_name' => 'CA', 'league' => 'L']);
        $event = Event::query()->create([
            'id' => 99301,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $cache = app(EventShowCache::class);

        $this->assertFalse(Cache::store('array')->has($cache->cacheKey($event)));

        $this->get(route('events.show', $event))->assertOk();

        $this->assertTrue(Cache::store('array')->has($cache->cacheKey($event)));
    }

    public function test_event_serves_cached_main_after_underlying_data_changes(): void
    {
        Cache::store('array')->flush();

        $home = Team::query()->create(['name' => 'Cached Home', 'short_name' => 'CH', 'league' => 'L']);
        $away = Team::query()->create(['name' => 'Cached Away', 'short_name' => 'CA', 'league' => 'L']);
        $event = Event::query()->create([
            'id' => 99302,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Cached Home', false);

        $home->update(['name' => 'Changed Home Name']);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Cached Home', false)
            ->assertDontSee('Changed Home Name', false);
    }

    public function test_event_cache_is_cleared_when_user_bet_is_saved(): void
    {
        Cache::store('array')->flush();

        $home = Team::query()->create(['name' => 'Tip Home', 'short_name' => 'TH', 'league' => 'L']);
        $away = Team::query()->create(['name' => 'Tip Away', 'short_name' => 'TA', 'league' => 'L']);
        $event = Event::query()->create([
            'id' => 99305,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $market = \App\Models\Market::query()->create([
            'id' => 993051,
            'event_id' => $event->id,
            'type' => \App\Models\Market::TYPE_MATCH_RESULT,
            'period' => \App\Models\Market::PERIOD_FULL_TIME,
            'line' => null,
            'status' => \App\Models\Market::STATUS_OPEN,
            'is_supported_market' => true,
        ]);
        $selection = \App\Models\Selection::query()->create([
            'id' => 993052,
            'market_id' => $market->id,
            'name' => \App\Models\Selection::NAME_HOME,
            'participant_id' => null,
            'handicap' => null,
            'created_at' => now(),
        ]);
        $odd = \App\Models\Odd::query()->create([
            'id' => 993053,
            'selection_id' => $selection->id,
            'odds' => 2.0,
            'probability' => null,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $cache = app(EventShowCache::class);

        $this->get(route('events.show', $event))->assertOk();
        $this->assertTrue(Cache::store('array')->has($cache->cacheKey($event)));

        \App\Models\UserBet::query()->create([
            'user_id' => User::factory()->create()->id,
            'event_id' => $event->id,
            'odd_id' => $odd->id,
            'stake' => 10,
            'odds_at_bet' => 2.0,
            'potential_return' => 20,
            'status' => \App\Models\UserBet::STATUS_PENDING,
        ]);

        $this->assertFalse(Cache::store('array')->has($cache->cacheKey($event)));
    }

    public function test_event_cache_key_varies_by_viewer_privileges(): void
    {
        Cache::store('array')->flush();

        $home = Team::query()->create(['name' => 'Priv Home', 'short_name' => 'PH', 'league' => 'L']);
        $away = Team::query()->create(['name' => 'Priv Away', 'short_name' => 'PA', 'league' => 'L']);
        $event = Event::query()->create([
            'id' => 99303,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $cache = app(EventShowCache::class);

        $this->get(route('events.show', $event))->assertOk();
        $this->assertTrue(Cache::store('array')->has($cache->cacheKey($event, 'en', 'guest')));

        $user = User::factory()->create();
        $user->grantPrivelege(User::PRIVELEGE_PLACE_BETS);

        $this->actingAs($user)->get(route('events.show', $event))->assertOk();
        $this->assertTrue(Cache::store('array')->has($cache->cacheKey($event, 'en', 'auth.place')));
        $this->assertNotSame(
            $cache->cacheKey($event, 'en', 'guest'),
            $cache->cacheKey($event, 'en', 'auth.place'),
        );
    }

    public function test_header_is_not_cached_on_event_page(): void
    {
        Cache::store('array')->flush();

        $home = Team::query()->create(['name' => 'Header Home', 'short_name' => 'HH', 'league' => 'L']);
        $away = Team::query()->create(['name' => 'Header Away', 'short_name' => 'HA', 'league' => 'L']);
        $event = Event::query()->create([
            'id' => 99304,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $user = User::factory()->create();

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee(__('Login'), false);

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee(__('Log out'), false);
    }
}
