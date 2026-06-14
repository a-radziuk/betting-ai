<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAnalysis;
use App\Models\UserBet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EventShowDataService
{
    /**
     * @return array{
     *     event: Event,
     *     eventBets: Collection<int, UserBet>,
     *     eventAnalysis: ?EventAnalysis,
     *     tournament: ?\App\Models\Tournament,
     *     standingsRows: list<array<string, mixed>>,
     *     standingsGroups: list<array{name: string, rows: list<array<string, mixed>>}>,
     *     standingsPromrel: array<string, mixed>
     * }
     */
    public function get(Event $event): array
    {
        $event->load([
            'homeTeam.translations',
            'awayTeam.translations',
            'tournament.translations',
            'markets' => fn ($query) => $query
                ->where('is_supported_market', true)
                ->with([
                    'selections.odds' => fn ($oddsQuery) => $oddsQuery->orderByDesc('created_at'),
                ]),
        ]);

        /** @var Collection<int, UserBet> $eventBets */
        $eventBets = collect();
        if (Schema::hasTable('user_bets')) {
            $eventBetsQuery = UserBet::query()
                ->where('user_bets.event_id', $event->id)
                ->whereHas('user', function ($q): void {
                    $q->whereHas('bets', function ($bq): void {
                        $bq->where('status', '<>', UserBet::STATUS_PENDING);
                    });
                })
                ->with([
                    'user.wallet',
                    'user.bets' => UserBet::eagerLoadRecentResolved(),
                    'odd.selection.market',
                ]);

            if (Schema::hasTable('user_wallets')) {
                $eventBets = $eventBetsQuery
                    ->join('users', 'users.id', '=', 'user_bets.user_id')
                    ->leftJoin('user_wallets', 'user_wallets.user_id', '=', 'users.id')
                    ->select('user_bets.*')
                    ->orderByDesc(DB::raw('COALESCE(user_wallets.total_result, 0)'))
                    ->orderBy('users.id')
                    ->orderByDesc('user_bets.id')
                    ->get();
            } else {
                $eventBets = $eventBetsQuery
                    ->orderByDesc('user_bets.id')
                    ->get();
            }
        }

        $eventAnalysis = null;
        if (Schema::hasTable('event_analyses')) {
            $eventAnalysis = EventAnalysis::query()
                ->where('event_id', $event->id)
                ->orderByDesc('strength')
                ->orderByDesc('id')
                ->first();
        }

        $tournament = $event->tournament;
        $standingsRows = [];
        $standingsGroups = [];
        $standingsPromrel = [];
        if ($tournament !== null) {
            $standingsRows = $tournament->localizedStandingsRows();
            $standingsGroups = $tournament->localizedStandingsGroups();
            $standingsPromrel = is_array($tournament->standings_promrel) ? $tournament->standings_promrel : [];
        }

        return [
            'event' => $event,
            'eventBets' => $eventBets,
            'eventAnalysis' => $eventAnalysis,
            'tournament' => $tournament,
            'standingsRows' => $standingsRows,
            'standingsGroups' => $standingsGroups ?? [],
            'standingsPromrel' => $standingsPromrel,
        ];
    }
}
