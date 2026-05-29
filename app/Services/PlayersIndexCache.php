<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PlayersIndexCache
{
    public function __construct(
        private readonly PlayersIndexDataService $playersIndexData,
    ) {}

    public function mainContentHtml(int $page = 1): string
    {
        if (! config('page_cache.cache_enabled')) {
            return $this->renderMainContent($page);
        }

        return Cache::store(config('page_cache.cache_store'))->remember(
            $this->cacheKey($page),
            config('page_cache.cache_ttl'),
            fn (): string => $this->renderMainContent($page),
        );
    }

    public function cacheKey(int $page = 1, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return 'players.index.'.$locale.'.page.'.$page;
    }

    public function forget(int $page = 1, ?string $locale = null): bool
    {
        return Cache::store(config('page_cache.cache_store'))->forget($this->cacheKey($page, $locale));
    }

    private function renderMainContent(int $page): string
    {
        return view('players.main', $this->playersIndexData->get($page))->render();
    }
}
