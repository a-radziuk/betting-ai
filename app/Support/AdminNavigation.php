<?php

namespace App\Support;

use App\Models\User;

final class AdminNavigation
{
    /**
     * @return list<array{label: string, route: string, active: string, feature?: string, editor?: bool}>
     */
    public static function items(): array
    {
        return [
            ['label' => __('Users'), 'route' => 'admin.users', 'active' => 'admin.users*'],
            ['label' => __('User Bets'), 'route' => 'admin.user-bets', 'active' => 'admin.user-bets*'],
            ['label' => __('Simple Crypto Payments'), 'route' => 'admin.simple-crypto-payments', 'active' => 'admin.simple-crypto-payments*', 'feature' => 'simple_crypto_payment'],
            ['label' => __('Upload Events'), 'route' => 'admin.upload-events', 'active' => 'admin.upload-events*'],
            ['label' => __('Upload Tournament'), 'route' => 'admin.upload-tournament', 'active' => 'admin.upload-tournament*'],
            ['label' => __('Import standings'), 'route' => 'admin.upload-standings', 'active' => 'admin.upload-standings*'],
            ['label' => __('Upload Analysis'), 'route' => 'admin.upload-analysis', 'active' => 'admin.upload-analysis*'],
            ['label' => __('Upload Predictions'), 'route' => 'admin.upload-predictions', 'active' => 'admin.upload-predictions*'],
            ['label' => __('Resolve Event'), 'route' => 'admin.resolve-event', 'active' => 'admin.resolve-event*'],
            ['label' => __('Site Texts'), 'route' => 'admin.site-texts', 'active' => 'admin.site-texts*', 'editor' => true],
            ['label' => __('SEO Pages'), 'route' => 'admin.seo-pages', 'active' => 'admin.seo-pages*', 'editor' => true],
            ['label' => __('Legal Pages'), 'route' => 'admin.legal-pages', 'active' => 'admin.legal-pages*', 'editor' => true],
            ['label' => __('Blog'), 'route' => 'admin.blogs', 'active' => 'admin.blogs*', 'editor' => true],
        ];
    }

    /**
     * @return list<array{label: string, route: string, active: string, feature?: string, editor?: bool}>
     */
    public static function visibleItems(?User $user): array
    {
        return array_values(array_filter(
            self::items(),
            fn (array $item): bool => self::itemVisibleTo($user, $item),
        ));
    }

    public static function homeRouteName(User $user): string
    {
        if ($user->isSuperadmin()) {
            return 'admin';
        }

        return 'admin.site-texts';
    }

    /**
     * @param  array{label: string, route: string, active: string, feature?: string, editor?: bool}  $item
     */
    private static function itemVisibleTo(?User $user, array $item): bool
    {
        if ($user === null) {
            return false;
        }

        if (isset($item['feature']) && ! feature($item['feature'])) {
            return false;
        }

        if ($user->isSuperadmin()) {
            return true;
        }

        return ($item['editor'] ?? false) && $user->hasPrivelege(User::PRIVELEGE_EDITOR);
    }
}
