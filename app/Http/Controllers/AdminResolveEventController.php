<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventAbandonService;
use App\Services\EventResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminResolveEventController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->readyToResolve()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        return view('admin.resolve-event', [
            'events' => $events,
            'timezone' => config('app.timezone'),
        ]);
    }

    public function show(Event $event): View|RedirectResponse
    {
        if ($this->isResolved($event)) {
            return redirect()
                ->route('admin.resolve-event')
                ->with('status', __('This event is already resolved.'));
        }

        $event->load(['homeTeam', 'awayTeam', 'tournament']);

        return view('admin.resolve-event-show', [
            'event' => $event,
            'timezone' => config('app.timezone'),
        ]);
    }

    public function store(Request $request, Event $event, EventResultService $eventResultService): RedirectResponse
    {
        if ($this->isResolved($event)) {
            return redirect()
                ->route('admin.resolve-event')
                ->with('status', __('This event is already resolved.'));
        }

        $validated = $request->validate([
            'score' => [
                'required',
                'string',
                'regex:/^\d+\s*[:-–]\s*\d+$/u',
            ],
        ], [
            'score.regex' => __('Invalid score format. Use e.g. 2:3 or 2-3.'),
        ]);

        $result = $eventResultService->applyEventResult(
            $event->id,
            trim($validated['score']),
            []
        );

        if (! $result['ok']) {
            return redirect()
                ->route('admin.resolve-event.show', $event)
                ->withErrors(['score' => $result['message']])
                ->withInput();
        }

        return redirect()
            ->route('admin.resolve-event')
            ->with('status', $result['message']);
    }

    public function abandon(Request $request, Event $event, EventAbandonService $eventAbandonService): RedirectResponse
    {
        if ($this->isResolved($event)) {
            return redirect()
                ->route('admin.resolve-event')
                ->with('status', __('This event is already resolved.'));
        }

        $validated = $request->validate([
            'comment' => ['nullable', 'string'],
        ]);

        $result = $eventAbandonService->abandon(
            $event->id,
            $validated['comment'] ?? null
        );

        if (! $result['ok']) {
            return redirect()
                ->route('admin.resolve-event.show', $event)
                ->withErrors(['comment' => $result['message']])
                ->withInput();
        }

        return redirect()
            ->route('admin.resolve-event')
            ->with('status', $result['message']);
    }

    private function isResolved(Event $event): bool
    {
        return $event->status === Event::STATUS_FINISHED;
    }
}
