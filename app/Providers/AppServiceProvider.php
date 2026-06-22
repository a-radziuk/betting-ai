<?php

namespace App\Providers;

use App\Models\LegalPage;
use App\Support\FaqPageContent;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\SiteTextRepository::class);
        $this->app->singleton(\App\Services\SeoPageRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('user', function (string $value): User {
            $query = User::query()->whereKey($value);

            if (! request()->is('admin', 'admin/*')) {
                $query->visibleOnSite();
            }

            return $query->firstOrFail();
        });

        Blade::if('feature', fn (string $name): bool => feature($name));

        View::composer(['layouts.partials.betai-footer', 'layouts.partials.betai-header'], function ($view): void {
            $legalPages = collect();
            $faqPage = null;

            if (Schema::hasTable('legal_pages')) {
                $faqPage = FaqPageContent::page();

                $legalPages = LegalPage::query()
                    ->when(
                        $faqPage !== null,
                        fn ($query) => $query->where('slug', '!=', FaqPageContent::slug()),
                    )
                    ->orderBy('title')
                    ->get(['id', 'title', 'slug']);
            }

            $view->with([
                'legalPages' => $legalPages,
                'faqPage' => $faqPage,
            ]);
        });
    }
}
