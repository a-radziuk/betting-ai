<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PlayerShowCache
{
    public function __construct(
        private readonly PlayerShowDataService $playerShowData,
    ) {}

    public function mainContentHtml(User $user, int $page = 1): string
    {
        if (! config('page_cache.cache_enabled')) {
            return $this->renderMainContent($user, $page);
        }

        return Cache::store(config('page_cache.cache_store'))->remember(
            $this->cacheKey($user, $page),
            config('page_cache.cache_ttl'),
            fn (): string => $this->renderMainContent($user, $page),
        );
    }

    public function cacheKey(User $user, int $page = 1, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return 'players.show.'.$user->id.'.'.$locale.'.page.'.$page;
    }

    public function forget(User $user, int $page = 1, ?string $locale = null): bool
    {
        return Cache::store(config('page_cache.cache_store'))->forget($this->cacheKey($user, $page, $locale));
    }

    private function renderMainContent(User $user, int $page): string
    {
        return view('player-stats.main', $this->playerShowData->get($user, $page))->render();
    }
}
