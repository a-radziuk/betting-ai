<?php

namespace Tests\Feature;

use App\Services\HomepageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ClearPagesCacheCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_flushes_the_configured_page_cache_store(): void
    {
        config([
            'page_cache.cache_enabled' => true,
            'page_cache.cache_store' => 'array',
            'page_cache.cache_ttl' => 300,
        ]);

        Cache::store('array')->flush();

        $cache = app(HomepageCache::class);
        $this->get('/')->assertOk();
        $this->assertTrue(Cache::store('array')->has($cache->cacheKey()));

        $exitCode = Artisan::call('pages:cache-clear');

        $this->assertSame(0, $exitCode);
        $this->assertFalse(Cache::store('array')->has($cache->cacheKey()));
    }

    public function test_it_accepts_a_custom_store_option(): void
    {
        config([
            'page_cache.cache_store' => 'array',
        ]);

        Cache::store('array')->put('custom-key', 'value', 60);
        $this->assertTrue(Cache::store('array')->has('custom-key'));

        Artisan::call('pages:cache-clear', ['--store' => 'array']);

        $this->assertFalse(Cache::store('array')->has('custom-key'));
    }
}
