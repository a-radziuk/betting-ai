<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class HomepageCache
{
    public function __construct(
        private readonly HomepageDataService $homepageData,
    ) {}

    public function mainContentHtml(): string
    {
        if (! config('page_cache.cache_enabled')) {
            return $this->renderMainContent();
        }

        $key = $this->cacheKey();

        return Cache::store(config('page_cache.cache_store'))->remember(
            $key,
            config('page_cache.cache_ttl'),
            fn (): string => $this->renderMainContent(),
        );
    }

    public function cacheKey(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return 'homepage.main.'.$locale;
    }

    public function forget(?string $locale = null): bool
    {
        return Cache::store(config('page_cache.cache_store'))->forget($this->cacheKey($locale));
    }

    private function renderMainContent(): string
    {
        return view('welcome.main', $this->homepageData->get())->render();
    }
}
