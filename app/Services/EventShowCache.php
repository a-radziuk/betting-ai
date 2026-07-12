<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class EventShowCache
{
    public function __construct(
        private readonly EventShowDataService $eventShowData,
    ) {}

    public function mainContentHtml(Event $event): string
    {
        if (! config('page_cache.cache_enabled')) {
            return $this->renderMainContent($event);
        }

        return Cache::store(config('page_cache.cache_store'))->remember(
            $this->cacheKey($event),
            config('page_cache.cache_ttl'),
            fn (): string => $this->renderMainContent($event),
        );
    }

    public function cacheKey(Event $event, ?string $locale = null, ?string $viewerKey = null): string
    {
        $locale ??= app()->getLocale();
        $viewerKey ??= $this->viewerCacheKey();

        return 'event.show.'.$event->id.'.'.$locale.'.'.$viewerKey;
    }

    public function forget(Event $event, ?string $locale = null, ?string $viewerKey = null): bool
    {
        return Cache::store(config('page_cache.cache_store'))->forget(
            $this->cacheKey($event, $locale, $viewerKey),
        );
    }

    /**
     * @return list<string>
     */
    public static function viewerCacheKeys(): array
    {
        return [
            'guest',
            'auth',
            'auth.tips',
            'auth.place',
            'auth.tips.place',
        ];
    }

    public function forgetAllViewerVariants(Event $event): void
    {
        $store = Cache::store(config('page_cache.cache_store'));

        foreach (['en'] as $locale) {
            foreach (self::viewerCacheKeys() as $viewerKey) {
                $store->forget($this->cacheKey($event, $locale, $viewerKey));
            }
        }
    }

    public function viewerCacheKey(): string
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return 'guest';
        }

        $parts = ['auth'];
        if ($user->hasPrivelege(User::PRIVELEGE_SEE_TIPS)) {
            $parts[] = 'tips';
        }
        if ($user->hasPrivelege(User::PRIVELEGE_PLACE_BETS)) {
            $parts[] = 'place';
        }

        return implode('.', $parts);
    }

    private function renderMainContent(Event $event): string
    {
        return view('event.main', $this->eventShowData->get($event))->render();
    }
}
