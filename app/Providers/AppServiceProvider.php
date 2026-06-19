<?php

namespace App\Providers;

use App\Models\LegalPage;
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
        //
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
