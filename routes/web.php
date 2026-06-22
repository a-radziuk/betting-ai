<?php

use App\Http\Controllers\AdminLegalPagesController;
use App\Http\Controllers\AdminSeoPagesController;
use App\Http\Controllers\AdminSiteTextsController;
use App\Http\Controllers\AdminResolveEventController;
use App\Http\Controllers\AdminSimpleCryptoPaymentsController;
use App\Http\Controllers\AdminUploadAnalysisController;
use App\Http\Controllers\AdminUploadEventsController;
use App\Http\Controllers\AdminUploadPredictionsController;
use App\Http\Controllers\AdminUploadStandingsController;
use App\Http\Controllers\AdminUploadTournamentController;
use App\Http\Controllers\AdminUserBetsController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\CryptoWebhookController;
use App\Http\Controllers\EventShowController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\PlayerResolvedBetsCsvController;
use App\Http\Controllers\PlayerResultTrendController;
use App\Http\Controllers\PlayerShowController;
use App\Http\Controllers\PlayersIndexController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\SubscribePromocodeController;
use App\Http\Controllers\SubscribeTermsController;
use App\Http\Controllers\SubscriptionCryptoPaymentController;
use App\Http\Controllers\SubscriptionPaymentCompleteController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubscriptionStripePaymentIntentController;
use App\Http\Controllers\TournamentShowController;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Odd;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserSubscription;
use App\PayWithMetamask\Http\Controllers\RecordTransactionController;
use App\Services\PlaceBetService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', HomeController::class);

Route::get('/faq', FaqController::class)->name('faq');

Route::get('/legal/{slug}', [LegalPageController::class, 'show'])
    ->name('legal.show');

Route::post('/cookie-consent', [CookieConsentController::class, 'store'])
    ->name('cookie-consent.store');

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

Route::get('/tournaments/{tournament}', TournamentShowController::class)->name('tournaments.show');

Route::get('/events/{event}', EventShowController::class)->name('events.show');

Route::get('/players', PlayersIndexController::class)->name('players.index');

Route::get('/players/{user}', PlayerShowController::class)->name('players.show');

Route::get('/players/{user}/bets.csv', PlayerResolvedBetsCsvController::class)
    ->name('players.bets.csv');

Route::get('/players/{user}/result-trend', PlayerResultTrendController::class)
    ->name('players.result-trend');

Route::get('/players/{user}/current', function (User $user) {
    $viewer = Auth::user();
    if ($viewer === null) {
        return redirect()->route('login');
    }

    $canSeeTips = $viewer->id === $user->id
        || $viewer->hasPrivelege(User::PRIVELEGE_SEE_TIPS);

    $bets = collect();
    if ($canSeeTips) {
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
    }

    return view('player-current-bets', [
        'player' => $user,
        'bets' => $bets,
        'canSeeTips' => $canSeeTips,
    ]);
})->name('players.current');

Route::get('/dashboard', function () {
    $user = auth()->user();
    $canPlaceBets = $user->hasPrivelege(User::PRIVELEGE_PLACE_BETS);

    $wallet = null;
    $bets = null;

    if ($canPlaceBets) {
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

        $wallet = $user->wallet;
    }

    return view('dashboard', [
        'canPlaceBets' => $canPlaceBets,
        'wallet' => $wallet,
        'bets' => $bets,
        'hasActiveSeeTips' => $user->hasActiveSeeTipsAccess(),
        'seeTipsExpiresAt' => $user->see_tips_expires_at,
    ]);
})->middleware(['auth'])->name('dashboard');

Route::middleware(['admin.access'])->prefix('admin')->group(function (): void {
    Route::get('/', function () {
        return view('admin.index');
    })->name('admin');

    Route::get('/users', [AdminUsersController::class, 'index'])
        ->name('admin.users');
    Route::get('/users/create', [AdminUsersController::class, 'create'])
        ->name('admin.users.create');
    Route::post('/users', [AdminUsersController::class, 'store'])
        ->name('admin.users.store');
    Route::get('/users/{user}/edit', [AdminUsersController::class, 'edit'])
        ->name('admin.users.edit');
    Route::put('/users/{user}', [AdminUsersController::class, 'update'])
        ->name('admin.users.update');
    Route::patch('/users/{user}/metrics-availability', [AdminUsersController::class, 'updateMetricsAvailability'])
        ->name('admin.users.metrics-availability');
    Route::delete('/users/{user}', [AdminUsersController::class, 'destroy'])
        ->name('admin.users.destroy');

    Route::get('/user-bets', [AdminUserBetsController::class, 'index'])
        ->name('admin.user-bets');
    Route::delete('/user-bets/{bet}', [AdminUserBetsController::class, 'destroy'])
        ->name('admin.user-bets.destroy');

    Route::get('/upload-events', [AdminUploadEventsController::class, 'show'])
        ->name('admin.upload-events');
    Route::post('/upload-events', [AdminUploadEventsController::class, 'store'])
        ->name('admin.upload-events.store');

    Route::get('/upload-tournament', [AdminUploadTournamentController::class, 'show'])
        ->name('admin.upload-tournament');
    Route::post('/upload-tournament', [AdminUploadTournamentController::class, 'store'])
        ->name('admin.upload-tournament.store');

    Route::get('/upload-standings', [AdminUploadStandingsController::class, 'show'])
        ->name('admin.upload-standings');
    Route::post('/upload-standings', [AdminUploadStandingsController::class, 'store'])
        ->name('admin.upload-standings.store');

    Route::get('/upload-analysis', [AdminUploadAnalysisController::class, 'show'])
        ->name('admin.upload-analysis');
    Route::post('/upload-analysis', [AdminUploadAnalysisController::class, 'store'])
        ->name('admin.upload-analysis.store');

    Route::get('/upload-predictions', [AdminUploadPredictionsController::class, 'show'])
        ->name('admin.upload-predictions');
    Route::post('/upload-predictions', [AdminUploadPredictionsController::class, 'store'])
        ->name('admin.upload-predictions.store');

    Route::get('/resolve-event', [AdminResolveEventController::class, 'index'])
        ->name('admin.resolve-event');
    Route::get('/resolve-event/{event}', [AdminResolveEventController::class, 'show'])
        ->name('admin.resolve-event.show');
    Route::post('/resolve-event/{event}', [AdminResolveEventController::class, 'store'])
        ->name('admin.resolve-event.store');
    Route::post('/resolve-event/{event}/abandon', [AdminResolveEventController::class, 'abandon'])
        ->name('admin.resolve-event.abandon');

    Route::get('/simple-crypto-payments', [AdminSimpleCryptoPaymentsController::class, 'index'])
        ->name('admin.simple-crypto-payments');
    Route::post('/simple-crypto-payments/{payment}/approve', [AdminSimpleCryptoPaymentsController::class, 'approve'])
        ->name('admin.simple-crypto-payments.approve');

    Route::get('/legal-pages', [AdminLegalPagesController::class, 'index'])
        ->name('admin.legal-pages');
    Route::get('/legal-pages/create', [AdminLegalPagesController::class, 'create'])
        ->name('admin.legal-pages.create');
    Route::post('/legal-pages', [AdminLegalPagesController::class, 'store'])
        ->name('admin.legal-pages.store');
    Route::get('/legal-pages/{legalPage}/edit', [AdminLegalPagesController::class, 'edit'])
        ->name('admin.legal-pages.edit');
    Route::put('/legal-pages/{legalPage}', [AdminLegalPagesController::class, 'update'])
        ->name('admin.legal-pages.update');
    Route::delete('/legal-pages/{legalPage}', [AdminLegalPagesController::class, 'destroy'])
        ->name('admin.legal-pages.destroy');

    Route::get('/site-texts', [AdminSiteTextsController::class, 'index'])
        ->name('admin.site-texts');
    Route::get('/site-texts/create', [AdminSiteTextsController::class, 'create'])
        ->name('admin.site-texts.create');
    Route::post('/site-texts', [AdminSiteTextsController::class, 'store'])
        ->name('admin.site-texts.store');
    Route::get('/site-texts/{siteText}/edit', [AdminSiteTextsController::class, 'edit'])
        ->name('admin.site-texts.edit');
    Route::put('/site-texts/{siteText}', [AdminSiteTextsController::class, 'update'])
        ->name('admin.site-texts.update');
    Route::delete('/site-texts/{siteText}', [AdminSiteTextsController::class, 'destroy'])
        ->name('admin.site-texts.destroy');

    Route::get('/seo-pages', [AdminSeoPagesController::class, 'index'])
        ->name('admin.seo-pages');
    Route::get('/seo-pages/{seoPage}/edit', [AdminSeoPagesController::class, 'edit'])
        ->name('admin.seo-pages.edit');
    Route::put('/seo-pages/{seoPage}', [AdminSeoPagesController::class, 'update'])
        ->name('admin.seo-pages.update');
});

Route::get('/subscribe', SubscribeController::class)->name('subscribe');
Route::post('/subscribe/promocode', [SubscribePromocodeController::class, 'store'])
    ->name('subscribe.promocode');

Route::middleware('auth')->group(function (): void {
    Route::get('/subscribe/terms/{plan}', [SubscribeTermsController::class, 'show'])
        ->name('subscribe.terms');
    Route::post('/subscribe/terms/{plan}', [SubscribeTermsController::class, 'store'])
        ->name('subscribe.terms.accept');

    Route::get('/subscribe/payment/{plan}', SubscriptionPaymentController::class)
        ->name('subscribe.payment');

    Route::post('/subscribe/payment/{plan}/stripe-intent', SubscriptionStripePaymentIntentController::class)
        ->name('subscribe.payment.stripe-intent');

    Route::post('/subscribe/payment/{plan}/metamask', RecordTransactionController::class)
        ->name('subscribe.payment.metamask');

    Route::get('/subscribe/payment/{plan}/complete', SubscriptionPaymentCompleteController::class)
        ->name('subscribe.payment.complete');

    Route::get('/subscribe/payment/{plan}/crypto/{wallet}', [SubscriptionCryptoPaymentController::class, 'show'])
        ->name('subscribe.payment.crypto');
    Route::post('/subscribe/payment/{plan}/crypto/{wallet}/paid', [SubscriptionCryptoPaymentController::class, 'markPaid'])
        ->name('subscribe.payment.crypto.paid');
});

Route::post('/crypto/webhook', CryptoWebhookController::class)
    ->name('crypto.webhook');

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook');

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

    Route::middleware('can.place.bets')->group(function (): void {
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
                'explanation' => ['nullable', 'string', 'max:65535'],
            ]);

            $explanation = isset($validated['explanation'])
                ? trim($validated['explanation'])
                : '';
            $explanation = $explanation !== '' ? $explanation : null;

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

            $result = $placeBetService->placeBet($user->id, $odd->id, (string) $validated['sum'], null, $explanation);
            if (! $result['ok']) {
                return back()
                    ->withInput()
                    ->withErrors(['sum' => $result['message']]);
            }

            return redirect()
                ->route('dashboard')
                ->with('status', $result['message']);
        })->name('bets.place.store');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
