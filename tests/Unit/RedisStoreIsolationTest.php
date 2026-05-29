<?php

namespace Tests\Unit;

use App\Support\RedisStoreIsolation;
use Tests\TestCase;

class RedisStoreIsolationTest extends TestCase
{
    public function test_session_and_cache_use_separate_redis_databases_by_default(): void
    {
        $this->assertSame('2', RedisStoreIsolation::connectionDatabase('session'));
        $this->assertSame('1', RedisStoreIsolation::connectionDatabase('cache'));
        $this->assertNotSame(
            RedisStoreIsolation::connectionDatabase('session'),
            RedisStoreIsolation::connectionDatabase('cache'),
        );
    }

    public function test_detects_when_session_and_page_cache_share_redis_database(): void
    {
        config([
            'session.driver' => 'redis',
            'session.connection' => 'session',
            'database.redis.session.database' => '1',
            'database.redis.cache.database' => '1',
            'page_cache.cache_store' => 'redis',
        ]);

        $conflict = RedisStoreIsolation::sessionCacheConflict();

        $this->assertNotNull($conflict);
        $this->assertSame(['session', 'cache'], $conflict);
    }

    public function test_no_conflict_when_databases_differ(): void
    {
        config([
            'session.driver' => 'redis',
            'session.connection' => 'session',
            'database.redis.session.database' => '2',
            'database.redis.cache.database' => '1',
            'page_cache.cache_store' => 'redis',
        ]);

        $this->assertNull(RedisStoreIsolation::sessionCacheConflict());
    }
}
