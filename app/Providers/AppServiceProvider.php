<?php

namespace App\Providers;

use App\Models\LegalPage;
use Illuminate\Support\Facades\Blade;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('feature', fn (string $name): bool => feature($name));

        View::composer('layouts.partials.betai-footer', function ($view): void {
            $legalPages = collect();

            if (Schema::hasTable('legal_pages')) {
                $legalPages = LegalPage::query()
                    ->orderBy('title')
                    ->get(['id', 'title', 'slug']);
            }

            $view->with('legalPages', $legalPages);
        });
    }
}
