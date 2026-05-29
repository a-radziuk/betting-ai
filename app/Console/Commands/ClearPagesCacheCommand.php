<?php

namespace App\Console\Commands;

use App\Support\RedisStoreIsolation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearPagesCacheCommand extends Command
{
    protected $signature = 'pages:cache-clear
                            {--store= : Cache store to flush (default: page_cache.cache_store)}
                            {--force : Flush even if session and cache share the same Redis database}';

    protected $description = 'Clear cached public page main content (homepage, tournaments, events, players)';

    public function handle(): int
    {
        $store = $this->option('store') ?? config('page_cache.cache_store');

        if (! config('page_cache.cache_enabled') && $this->option('store') === null) {
            $this->warn('Page cache is disabled (PAGES_CACHE_ENABLED=false).');
        }

        $conflict = RedisStoreIsolation::sessionCacheConflict();
        if ($conflict !== null && ! $this->option('force')) {
            [$sessionConnection, $cacheConnection] = $conflict;
            $db = RedisStoreIsolation::connectionDatabase($cacheConnection);

            $this->error("Refusing to flush: sessions (redis connection [{$sessionConnection}]) and page cache (redis connection [{$cacheConnection}]) both use Redis database [{$db}].");
            $this->line('Use separate REDIS_SESSION_DB and REDIS_CACHE_DB in .env, or pass --force (this will log everyone out).');

            return self::FAILURE;
        }

        $cacheConnection = RedisStoreIsolation::cacheStoreConnection($store);
        if ($cacheConnection !== null) {
            $db = RedisStoreIsolation::connectionDatabase($cacheConnection);
            $this->info("Flushing Redis cache connection [{$cacheConnection}] (database {$db}) only — sessions are not affected.");
        }

        Cache::store($store)->flush();

        $this->info("Cleared cache store [{$store}].");

        return self::SUCCESS;
    }
}
