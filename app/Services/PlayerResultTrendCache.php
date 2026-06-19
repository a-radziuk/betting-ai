<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PlayerResultTrendCache
{
    public function __construct(
        private readonly PlayerShowDataService $playerShowData,
    ) {}

    public function mainContentHtml(User $user): string
    {
        if (! config('page_cache.cache_enabled')) {
            return $this->renderMainContent($user);
        }

        return Cache::store(config('page_cache.cache_store'))->remember(
            $this->cacheKey($user),
            config('page_cache.cache_ttl'),
            fn (): string => $this->renderMainContent($user),
        );
    }

    public function cacheKey(User $user, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return 'players.result-trend.'.$user->id.'.'.$locale;
    }

    public function forget(User $user, ?string $locale = null): bool
    {
        return Cache::store(config('page_cache.cache_store'))->forget($this->cacheKey($user, $locale));
    }

    private function renderMainContent(User $user): string
    {
        $user->loadMissing('wallet');

        return view('player-result-trend.main', [
            'player' => $user,
            'resultChart' => $this->playerShowData->buildFullResultChart($user),
            'resolvedBetCount' => $this->playerShowData->resolvedBetCount($user),
        ])->render();
    }
}
