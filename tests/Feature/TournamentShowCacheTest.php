<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\TournamentShowCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TournamentShowCacheTest extends TestCase
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

    public function test_tournament_main_content_is_stored_in_cache(): void
    {
        Cache::store('array')->flush();

        $tournament = Tournament::query()->create([
            'name' => 'Premier League',
            'rank' => 1,
        ]);

        $cache = app(TournamentShowCache::class);

        $this->assertFalse(Cache::store('array')->has($cache->cacheKey($tournament)));

        $this->get(route('tournaments.show', $tournament))->assertOk();

        $this->assertTrue(Cache::store('array')->has($cache->cacheKey($tournament)));
    }

    public function test_tournament_serves_cached_main_without_hitting_database_on_second_request(): void
    {
        Cache::store('array')->flush();

        $tournament = Tournament::query()->create(['name' => 'Test League', 'rank' => 1]);
        $home = Team::query()->create(['name' => 'Cached Home', 'short_name' => 'CH', 'league' => 'L']);
        $away = Team::query()->create(['name' => 'Cached Away', 'short_name' => 'CA', 'league' => 'L']);
        Event::query()->create([
            'id' => 99101,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $this->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('Cached Home', false);

        Event::query()->whereKey(99101)->delete();

        $this->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('Cached Home', false);
    }

    public function test_tournament_cache_key_includes_tournament_id_and_locale(): void
    {
        Cache::store('array')->flush();

        $tournament = Tournament::query()->create(['name' => 'League A', 'rank' => 1]);
        $cache = app(TournamentShowCache::class);

        config(['app.locale' => 'en']);
        app()->setLocale('en');
        $this->get(route('tournaments.show', $tournament))->assertOk();
        $this->assertTrue(Cache::store('array')->has($cache->cacheKey($tournament, 'en')));

        $other = Tournament::query()->create(['name' => 'League B', 'rank' => 1]);
        $this->assertFalse(Cache::store('array')->has($cache->cacheKey($other, 'en')));
    }
}
