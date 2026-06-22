<?php

namespace App\Support;

use App\Models\Event;
use App\Models\LegalPage;
use App\Models\SeoPage;
use App\Models\Tournament;
use App\Models\User;
use App\Services\SeoPageRepository;

final class PageSeo
{
    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forHome(): array
    {
        return self::forKey(SeoPage::KEY_HOMEPAGE);
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forPlayersIndex(): array
    {
        return self::forKey(SeoPage::KEY_PLAYERS_INDEX);
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forPlayerShow(User $user): array
    {
        return self::forKey(SeoPage::KEY_PLAYER_SHOW, [
            'name' => $user->name,
        ]);
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forTournamentShow(Tournament $tournament): array
    {
        return self::forKey(SeoPage::KEY_TOURNAMENT_SHOW, [
            'tournament' => $tournament->localizedName(),
        ]);
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forEventShow(Event $event): array
    {
        return self::forKey(SeoPage::KEY_EVENT_SHOW, [
            'event' => self::eventName($event),
        ]);
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forLogin(): array
    {
        return self::forKey(SeoPage::KEY_LOGIN);
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forRegister(): array
    {
        return self::forKey(SeoPage::KEY_REGISTER);
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forForgotPassword(): array
    {
        return self::forKey(SeoPage::KEY_FORGOT_PASSWORD);
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forLegalPage(LegalPage $page): array
    {
        $title = $page->meta_title
            ?: app_page_title(':title', ['title' => $page->title]);

        $description = $page->meta_description;
        $ogTitle = $page->og_title ?: $page->meta_title ?: $page->title;
        $ogDescription = $page->og_description ?: $page->meta_description;

        return self::merge([
            'title' => $title,
            'description' => $description,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
        ], self::defaults());
    }

    /**
     * @param  array<string, string>  $replace
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function forKey(string $key, array $replace = []): array
    {
        $page = app(SeoPageRepository::class)->get($key);

        $title = self::interpolate($page?->meta_title, $replace);
        $description = self::interpolate($page?->meta_description, $replace);
        $ogTitle = self::interpolate($page?->og_title ?: $page?->meta_title, $replace);
        $ogDescription = self::interpolate($page?->og_description ?: $page?->meta_description, $replace);

        return self::merge([
            'title' => $title,
            'description' => $description,
            'og_title' => $ogTitle,
            'og_description' => $ogDescription,
        ], self::defaults());
    }

    /**
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    public static function defaults(): array
    {
        $title = app_name();

        return [
            'title' => $title,
            'description' => null,
            'og_title' => $title,
            'og_description' => null,
        ];
    }

    public static function eventName(Event $event): string
    {
        $event->loadMissing('homeTeam', 'awayTeam');

        $home = $event->homeTeam?->resolvedDisplayName() ?? __('Home');
        $away = $event->awayTeam?->resolvedDisplayName() ?? __('Away');

        return "{$home} vs {$away}";
    }

    /**
     * @param  array{title?: string|null, description?: string|null, og_title?: string|null, og_description?: string|null}  $primary
     * @param  array{title: string, description: string|null, og_title: string|null, og_description: string|null}  $fallback
     * @return array{title: string, description: string|null, og_title: string|null, og_description: string|null}
     */
    private static function merge(array $primary, array $fallback): array
    {
        $title = $primary['title'] ?? null;
        $description = $primary['description'] ?? null;
        $ogTitle = $primary['og_title'] ?? null;
        $ogDescription = $primary['og_description'] ?? null;

        return [
            'title' => $title ?: $fallback['title'],
            'description' => $description ?: $fallback['description'],
            'og_title' => $ogTitle ?: $title ?: $fallback['og_title'],
            'og_description' => $ogDescription ?: $description ?: $fallback['og_description'],
        ];
    }

    /**
     * @param  array<string, string>  $replace
     */
    private static function interpolate(?string $value, array $replace = []): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        foreach (['app' => app_name()] + $replace as $name => $replacement) {
            $value = str_replace(':'.$name, (string) $replacement, $value);
        }

        return $value;
    }
}
