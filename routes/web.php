<?php

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
