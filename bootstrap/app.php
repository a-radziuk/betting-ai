<?php

use App\Http\Middleware\EnsureCanPlaceBets;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\RejectBots;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'can.place.bets' => EnsureCanPlaceBets::class,
            'reject.bots' => RejectBots::class,
            'superadmin' => EnsureSuperadmin::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'crypto/webhook',
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
