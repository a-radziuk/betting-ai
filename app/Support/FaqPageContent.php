<?php

namespace App\Support;

use App\Models\LegalPage;

final class FaqPageContent
{
    public static function slug(): string
    {
        return (string) config('legal.faq.slug', 'faq');
    }

    public static function page(): ?LegalPage
    {
        return LegalPage::query()
            ->where('slug', self::slug())
            ->first();
    }

    public static function isManagedPage(LegalPage $page): bool
    {
        return $page->slug === self::slug();
    }
}
