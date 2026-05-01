<?php

use App\Http\Controllers\ProfileController;
use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    /** @var Collection<int, Event> $events */
    $events = collect();

    if (Schema::hasTable('events')) {
        $events = Event::query()
            ->with(['homeTeam', 'awayTeam'])
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->limit(20)
            ->get();
    }

    return view('welcome', compact('events'));
});

Route::get('/events/{event}', function (Event $event) {
    $event->load([
        'homeTeam',
        'awayTeam',
        'markets.selections.odds' => fn ($query) => $query->orderByDesc('created_at'),
    ]);

    return view('event', compact('event'));
})->name('events.show');

Route::get('/dashboard', function () {
    $user = auth()->user();
    $user->loadMissing('wallet');
    if (! $user->wallet) {
        $user->wallet()->create([
            'balance' => 0,
            'currency' => 'EUR',
        ]);
        $user->load('wallet');
    }

    $bets = $user->bets()
        ->with([
            'event.homeTeam',
            'event.awayTeam',
            'odd.selection.market',
        ])
        ->latest()
        ->paginate(20)
        ->withQueryString();

    return view('dashboard', [
        'wallet' => $user->wallet,
        'bets' => $bets,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
