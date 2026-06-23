<?php

use App\Http\Controllers\Api\TelegramStartController;
use Illuminate\Support\Facades\Route;

Route::middleware('telegram.promobot.api')->group(function (): void {
    Route::post('/telegram/start', TelegramStartController::class)
        ->name('api.telegram.start');
});
