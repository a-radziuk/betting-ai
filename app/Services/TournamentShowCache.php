<?php

namespace App\Services;

use App\Models\Tournament;
use Illuminate\Support\Facades\Cache;

class TournamentShowCache
{
    public function __construct(
        private readonly TournamentShowDataService $tournamentShowData,
    ) {}

    public function mainContentHtml(Tournament $tournament): string
    {
        if (! config('page_cache.cache_enabled')) {
            return $this->renderMainContent($tournament);
        }

        return Cache::store(config('page_cache.cache_store'))->remember(
            $this->cacheKey($tournament),
            config('page_cache.cache_ttl'),
            fn (): string => $this->renderMainContent($tournament),
        );
    }

    public function cacheKey(Tournament $tournament, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return 'tournament.show.'.$tournament->id.'.'.$locale;
    }

    public function forget(Tournament $tournament, ?string $locale = null): bool
    {
        return Cache::store(config('page_cache.cache_store'))->forget($this->cacheKey($tournament, $locale));
    }

    private function renderMainContent(Tournament $tournament): string
    {
        return view('tournament-standings.main', $this->tournamentShowData->get($tournament))->render();
    }
}
