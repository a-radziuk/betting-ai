<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearPagesCacheCommand extends Command
{
    protected $signature = 'pages:cache-clear
                            {--store= : Cache store to flush (default: page_cache.cache_store)}';

    protected $description = 'Clear cached public page main content (homepage, tournaments, events, players)';

    public function handle(): int
    {
        $store = $this->option('store') ?? config('page_cache.cache_store');

        if (! config('page_cache.cache_enabled') && $this->option('store') === null) {
            $this->warn('Page cache is disabled (PAGES_CACHE_ENABLED=false).');
        }

        Cache::store($store)->flush();

        $this->info("Cleared cache store [{$store}].");

        return self::SUCCESS;
    }
}
