<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\HomepageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomepageCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'page_cache.cache_enabled' => true,
            // Use array store in tests (same remember/forget API as Redis).
            'page_cache.cache_store' => 'array',
            'page_cache.cache_ttl' => 300,
        ]);
    }

    public function test_homepage_main_content_is_stored_in_cache(): void
    {
        Cache::store('array')->flush();

        $cache = app(HomepageCache::class);

        $this->assertFalse(Cache::store('array')->has($cache->cacheKey()));

        $this->get('/')->assertOk();

        $this->assertTrue(Cache::store('array')->has($cache->cacheKey()));
        $this->assertNotSame('', Cache::store('array')->get($cache->cacheKey()));
    }

    public function test_homepage_serves_cached_main_without_hitting_database_on_second_request(): void
    {
        Cache::store('array')->flush();

        $tournament = Tournament::query()->create(['name' => 'Premier League', 'rank' => 1]);
        $home = Team::query()->create(['name' => 'Home', 'short_name' => 'H', 'league' => 'L']);
        $away = Team::query()->create(['name' => 'Away', 'short_name' => 'A', 'league' => 'L']);
        Event::query()->create([
            'id' => 99001,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'tournament_id' => $tournament->id,
            'start_time' => now()->addDay(),
            'status' => Event::STATUS_SCHEDULED,
        ]);

        $this->get('/')->assertOk()->assertSee('Home', false);

        Event::query()->whereKey(99001)->delete();

        $this->get('/')->assertOk()->assertSee('Home', false);
    }

    public function test_header_is_not_cached_and_reflects_authentication(): void
    {
        Cache::store('array')->flush();

        $user = User::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee(__('Login'), false);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee(__('Log out'), false);
    }

    public function test_cache_key_includes_locale(): void
    {
        Cache::store('array')->flush();

        $cache = app(HomepageCache::class);

        config(['app.locale' => 'en']);
        app()->setLocale('en');
        $this->get('/')->assertOk();
        $this->assertTrue(Cache::store('array')->has($cache->cacheKey('en')));

        config(['app.locale' => 'ru']);
        app()->setLocale('ru');
        $this->get('/')->assertOk();
        $this->assertTrue(Cache::store('array')->has($cache->cacheKey('ru')));
    }

    public function test_cache_can_be_disabled(): void
    {
        config(['page_cache.cache_enabled' => false]);
        Cache::store('array')->flush();

        $cache = app(HomepageCache::class);

        $this->get('/')->assertOk();

        $this->assertFalse(Cache::store('array')->has($cache->cacheKey()));
    }
}
