<?php

namespace App\Support;

use App\Models\UserBet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class HomepageFeaturedBets
{
    public const DISPLAY_LIMIT = 3;

    public const MAX_SCAN = 100;

    /**
     * @return Builder<UserBet>
     */
    public static function latestResolvedQuery(): Builder
    {
        return UserBet::query()
            ->join('events', 'events.id', '=', 'user_bets.event_id')
            ->where('user_bets.status', '<>', UserBet::STATUS_PENDING)
            ->whereHas('user', fn ($query) => $query->visibleOnSite())
            ->orderByDesc('events.start_time')
            ->orderByDesc('user_bets.id')
            ->select('user_bets.*');
    }

    /**
     * Walk bets in recent-event order and pick the first resolved bet per user.
     *
     * @param  Collection<int, UserBet>  $bets
     * @return Collection<int, UserBet>
     */
    public static function pickOnePerUserFromRecentEvents(Collection $bets, int $displayLimit = self::DISPLAY_LIMIT): Collection
    {
        $selected = collect();
        $seenUserIds = [];

        foreach ($bets as $bet) {
            $userId = (int) $bet->user_id;
            if (isset($seenUserIds[$userId])) {
                continue;
            }

            $seenUserIds[$userId] = true;
            $selected->push($bet);

            if ($selected->count() >= $displayLimit) {
                break;
            }
        }

        return $selected->values();
    }

    /**
     * @return Collection<int, UserBet>
     */
    public static function forHomepage(): Collection
    {
        $bets = self::latestResolvedQuery()
            ->with([
                'user.wallet',
                'event.homeTeam',
                'event.awayTeam',
                'odd.selection.market',
            ])
            ->limit(self::MAX_SCAN)
            ->get();

        return self::pickOnePerUserFromRecentEvents($bets);
    }
}
