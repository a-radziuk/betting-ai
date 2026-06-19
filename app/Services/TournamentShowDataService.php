<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventResult;
use App\Models\Market;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TournamentShowDataService
{
    /**
     * @return array{
     *     tournament: Tournament,
     *     standingsRows: list<array<string, mixed>>,
     *     standingsGroups: list<array{name: string, rows: list<array<string, mixed>>}>,
     *     standingsPromrel: array<string, mixed>,
     *     upcomingEvents: Collection<int, Event>,
     *     recentEventResults: Collection<int, EventResult>,
     *     eventResultsTotal: int
     * }
     */
    public function get(Tournament $tournament): array
    {
        $tournament->loadMissing('translations');

        $standingsPromrel = $tournament->standings_promrel;

        /** @var Collection<int, Event> $upcomingEvents */
        $upcomingEvents = collect();
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'tournament_id')) {
            $upcomingEvents = Event::query()
                ->with([
                    'homeTeam.translations',
                    'awayTeam.translations',
                    'tournament.translations',
                ])
                ->withCount(['userBets as user_bets_count' => function ($query): void {
                    $query->whereHas('user', fn ($userQuery) => $userQuery->visibleOnSite());
                }])
                ->with([
                    'markets' => function ($query): void {
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
                ->with(['homeTeam.translations', 'awayTeam.translations'])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
            $eventResultsTotal = EventResult::query()
                ->where('tournament_id', $tournament->id)
                ->count();
        }

        return [
            'tournament' => $tournament,
            'standingsRows' => $tournament->localizedStandingsRows(),
            'standingsGroups' => $tournament->localizedStandingsGroups(),
            'standingsPromrel' => $standingsPromrel,
            'upcomingEvents' => $upcomingEvents,
            'recentEventResults' => $recentEventResults,
            'eventResultsTotal' => $eventResultsTotal,
        ];
    }
}
