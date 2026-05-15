<?php

use App\Http\Controllers\ProfileController;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserSubscription;
use App\Services\PlaceBetService;
use App\Support\PlayerWalletResultChart;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function () {
    /** @var Collection<int, Event> $events */
    $events = collect();

    if (Schema::hasTable('events')) {
        $events = Event::query()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->with([
                'markets' => function ($query) {
                    $query->where('type', Market::TYPE_MATCH_RESULT)
                        ->where('is_supported_market', true)
                        ->with([
                            'selections.odds' => fn ($oddsQuery) => $oddsQuery->orderByDesc('created_at'),
                        ]);
                },
            ])
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->limit(20)
            ->get();
    }

    /** @var Collection<int, Tournament> $topTournaments */
    $topTournaments = collect();
    if (Schema::hasTable('tournaments')) {
        $topTournaments = Tournament::query()
            ->where('rank', 1)
            ->orderBy('name')
            ->get();
    }

    /** @var Collection<int, User> $topBettors */
    $topBettors = collect();
    if (
        Schema::hasTable('users')
        && Schema::hasTable('user_bets')
        && Schema::hasTable('user_wallets')
        && Schema::hasColumn('user_wallets', 'total_result')
    ) {
        $topBettors = User::query()
            ->has('bets')
            ->join('user_wallets', 'user_wallets.user_id', '=', 'users.id')
            ->orderByDesc('user_wallets.total_result')
            ->orderBy('users.id')
            ->select('users.*')
            ->withCount('bets')
            ->withSum('bets', 'stake')
            ->limit(3)
            ->with([
                'wallet',
                'bets' => fn ($q) => $q->where('status', '<>', UserBet::STATUS_PENDING)->orderByDesc('id')->limit(5),
            ])
            ->get();
    }

    return view('welcome', compact('events', 'topTournaments', 'topBettors'));
});

Route::get('/tournaments/{tournament}/results', function (Tournament $tournament) {
    /** @var Collection<int, EventResult> $allEventResults */
    $allEventResults = collect();
    if (Schema::hasTable('event_results')) {
        $allEventResults = EventResult::query()
            ->where('tournament_id', $tournament->id)
            ->with(['homeTeam', 'awayTeam'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }

    return view('tournament-results', compact('tournament', 'allEventResults'));
})->name('tournaments.results');

Route::get('/tournaments/{tournament}', function (Tournament $tournament) {
    $standingsPromrel = is_array($tournament->standings_promrel) ? $tournament->standings_promrel : [];

    /** @var Collection<int, Event> $upcomingEvents */
    $upcomingEvents = collect();
    if (Schema::hasTable('events') && Schema::hasColumn('events', 'tournament_id')) {
        $upcomingEvents = Event::query()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->with([
                'markets' => function ($query) {
                    $query->where('type', Market::TYPE_MATCH_RESULT)
                        ->where('is_supported_market', true)
                        ->with([
                            'selections.odds' => fn ($oddsQuery) => $oddsQuery->orderByDesc('created_at'),
                        ]);
                },
            ])
            ->where('tournament_id', $tournament->id)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->limit(20)
            ->get();
    }

    /** @var Collection<int, EventResult> $recentEventResults */
    $recentEventResults = collect();
    $eventResultsTotal = 0;
    if (Schema::hasTable('event_results')) {
        $recentEventResults = EventResult::query()
            ->where('tournament_id', $tournament->id)
            ->with(['homeTeam', 'awayTeam'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
        $eventResultsTotal = EventResult::query()
            ->where('tournament_id', $tournament->id)
            ->count();
    }

    return view('tournament-standings', [
        'tournament' => $tournament,
        'standingsRows' => is_array($tournament->standings) ? ($tournament->standings['rows'] ?? []) : [],
        'standingsPromrel' => $standingsPromrel,
        'upcomingEvents' => $upcomingEvents,
        'recentEventResults' => $recentEventResults,
        'eventResultsTotal' => $eventResultsTotal,
    ]);
})->name('tournaments.show');

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

Route::get('/players', function () {
    $players = User::query()
        ->leftJoin('user_wallets', 'user_wallets.user_id', '=', 'users.id')
        ->whereExists(function ($q): void {
            $q->selectRaw('1')
                ->from('user_bets')
                ->whereColumn('user_bets.user_id', 'users.id');
        })
        ->orderByDesc(DB::raw('COALESCE(user_wallets.total_result, 0)'))
        ->select([
            'users.id',
            'users.name',
            DB::raw('COALESCE(user_wallets.balance, 0) as wallet_balance'),
            DB::raw('COALESCE(user_wallets.amount_in_play, 0) as wallet_amount_in_play'),
            DB::raw('COALESCE(user_wallets.total_result, 0) as wallet_total_result'),
            DB::raw("COALESCE(user_wallets.currency, 'EUR') as wallet_currency"),
        ])
        ->with([
            'bets' => fn ($q) => $q
                ->where('status', '!=', UserBet::STATUS_PENDING)
                ->orderByDesc('id')
                ->limit(5),
        ])
        ->paginate(20)
        ->withQueryString();

    return view('players', [
        'players' => $players,
    ]);
})->name('players.index');

Route::get('/players/{user}', function (User $user) {
    $resolvedBetsQuery = UserBet::query()
        ->where('user_bets.user_id', $user->id)
        ->where('user_bets.status', '!=', UserBet::STATUS_PENDING)
        ->join('events', 'events.id', '=', 'user_bets.event_id')
        ->orderBy('user_bets.updated_at', 'desc')
    ;

    $chartValues = (clone $resolvedBetsQuery)
        ->orderByDesc('events.start_time')
        ->orderByDesc('user_bets.id')
        ->limit(30)
        ->pluck('user_bets.wallet_total_result')
        ->reverse()
        ->values()
        ->all();

    $resultChart = PlayerWalletResultChart::fromValues($chartValues);

    $bets = (clone $resolvedBetsQuery)
        ->orderByDesc('events.start_time')
        ->orderByDesc('user_bets.id')
        ->select('user_bets.*')
        ->with([
            'event.homeTeam',
            'event.awayTeam',
            'odd.selection.market',
        ])
        ->paginate(20)
        ->withQueryString();

    return view('player-stats', [
        'player' => $user,
        'bets' => $bets,
        'resultChart' => $resultChart,
    ]);
})->name('players.show');

Route::get('/players/{user}/current', function (User $user) {
    $viewer = Auth::user();
    if ($viewer === null) {
        return redirect()->route('login');
    }

    if ($viewer->id !== $user->id) {
        $isSubscribed = UserSubscription::query()
            ->where('subscriber_user_id', $viewer->id)
            ->where('player_user_id', $user->id)
            ->exists();
        if (! $isSubscribed) {
            return redirect()->route('players.subscribe.show', ['user' => $user->id]);
        }
    }

    $bets = UserBet::query()
        ->where('user_id', $user->id)
        ->where('user_bets.status', UserBet::STATUS_PENDING)
        ->join('events', 'events.id', '=', 'user_bets.event_id')
        ->orderBy('events.start_time')
        ->select('user_bets.*')
        ->with([
            'event.homeTeam',
            'event.awayTeam',
            'odd.selection.market',
        ])
        ->paginate(20)
        ->withQueryString();

    return view('player-current-bets', [
        'player' => $user,
        'bets' => $bets,
    ]);
})->name('players.current');

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
    Route::get('/players/{user}/subscribe', function (User $user) {
        $viewer = Auth::user();
        if ($viewer === null) {
            abort(403);
        }

        if ($viewer->id === $user->id) {
            abort(400, 'You cannot subscribe to yourself.');
        }

        $subscription = UserSubscription::query()
            ->where('subscriber_user_id', $viewer->id)
            ->where('player_user_id', $user->id)
            ->first();

        return view('player-subscribe', [
            'player' => $user,
            'subscription' => $subscription,
        ]);
    })->name('players.subscribe.show');

    Route::post('/players/{user}/subscribe', function (Request $request, User $user) {
        $viewer = Auth::user();
        if ($viewer === null) {
            abort(403);
        }

        if ($viewer->id === $user->id) {
            abort(400, 'You cannot subscribe to yourself.');
        }

        $sub = UserSubscription::query()->firstOrCreate([
            'subscriber_user_id' => $viewer->id,
            'player_user_id' => $user->id,
        ]);

        return redirect()
            ->route('players.subscribe.show', ['user' => $user->id])
            ->with('status', 'Subscribed.');
    })->name('players.subscribe.store');

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
