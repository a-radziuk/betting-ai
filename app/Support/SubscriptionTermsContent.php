<?php

namespace App\Support;

use App\Models\LegalPage;
use Illuminate\Support\Facades\View;

final class SubscriptionTermsContent
{
    public static function slug(): string
    {
        return (string) config('subscriptions.terms.slug', 'subscription-terms');
    }

    public static function page(): ?LegalPage
    {
        return LegalPage::query()
            ->where('slug', self::slug())
            ->first();
    }

    public static function version(): string
    {
        $page = self::page();

        if ($page?->updated_at !== null) {
            return (string) $page->updated_at->timestamp;
        }

        return (string) config('subscriptions.terms.version', '1');
    }

    public static function renderedContent(): string
    {
        $page = self::page();

        if ($page !== null) {
            return LegalPageContent::render($page->content);
        }

        return View::make('legal.subscription-terms-content')->render();
    }

    public static function isManagedPage(LegalPage $page): bool
    {
        return $page->slug === self::slug();
    }
}
