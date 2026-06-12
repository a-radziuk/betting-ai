<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Market;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserBet;
use App\Support\HomepageFeaturedBets;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class HomepageDataService
{
    /**
     * @return array{
     *     events: Collection<int, Event>,
     *     topTournaments: Collection<int, Tournament>,
     *     topBettors: Collection<int, User>,
     *     featuredBets: Collection<int, UserBet>
     * }
     */
    public function get(): array
    {
        /** @var Collection<int, Event> $events */
        $events = collect();

        if (Schema::hasTable('events')) {
            $events = Event::query()
                ->with([
                    'homeTeam.translations',
                    'awayTeam.translations',
                    'tournament.translations',
                ])
                ->withCount('userBets')
                ->with([
                    'markets' => function ($query): void {
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
                ->with('translations')
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
                ->whereHas('bets', function ($q): void {
                    $q->where('status', '<>', UserBet::STATUS_PENDING);
                })
                ->join('user_wallets', 'user_wallets.user_id', '=', 'users.id')
                ->orderByDesc('user_wallets.total_result')
                ->orderBy('users.id')
                ->select('users.*')
                ->withCount('bets')
                ->withSum('bets', 'stake')
                ->limit(3)
                ->with([
                    'wallet',
                    'bets' => UserBet::eagerLoadRecentResolved(),
                ])
                ->get();
        }

        /** @var Collection<int, UserBet> $featuredBets */
        $featuredBets = collect();
        if (Schema::hasTable('user_bets')) {
            $featuredBets = HomepageFeaturedBets::forHomepage();
        }

        return compact('events', 'topTournaments', 'topBettors', 'featuredBets');
    }
}
