<?php

use App\Http\Controllers\ProfileController;
use App\Models\Event;
use App\Models\Odd;
use App\Services\PlaceBetService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
        'markets' => fn ($query) => $query
            ->where('is_supported_market', true)
            ->with([
                'selections.odds' => fn ($oddsQuery) => $oddsQuery->orderByDesc('created_at'),
            ]),
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
    Route::get('/place-bet/{odd}', function (Odd $odd) {
        $odd->loadMissing('selection.market.event.homeTeam', 'selection.market.event.awayTeam');

        $event = $odd->selection?->market?->event;
        if ($event === null) {
            abort(404);
        }

        if ($event->status !== Event::STATUS_SCHEDULED) {
            abort(400, 'Event is not scheduled.');
        }

        $user = Auth::user();
        $user->loadMissing('wallet');
        if (! $user->wallet) {
            $user->wallet()->create([
                'balance' => 0,
                'currency' => 'EUR',
            ]);
            $user->load('wallet');
        }

        return view('place-bet', [
            'odd' => $odd,
            'event' => $event,
            'wallet' => $user->wallet,
        ]);
    })->name('bets.place.show');

    Route::post('/place-bet/{odd}', function (Request $request, Odd $odd, PlaceBetService $placeBetService) {
        $odd->loadMissing('selection.market.event');

        $event = $odd->selection?->market?->event;
        if ($event === null) {
            abort(404);
        }

        if ($event->status !== Event::STATUS_SCHEDULED) {
            abort(400, 'Event is not scheduled.');
        }

        $validated = $request->validate([
            'sum' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user = Auth::user();
        $user->loadMissing('wallet');
        if (! $user->wallet) {
            return back()->withErrors(['sum' => 'User has no wallet.']);
        }

        if ((float) $validated['sum'] > (float) $user->wallet->balance) {
            return back()
                ->withInput()
                ->withErrors(['sum' => 'Insufficient wallet balance.']);
        }

        $result = $placeBetService->placeBet($user->id, $odd->id, (string) $validated['sum']);
        if (! $result['ok']) {
            return back()
                ->withInput()
                ->withErrors(['sum' => $result['message']]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', $result['message']);
    })->name('bets.place.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
