<?php

namespace App\Support;

use App\Models\UserBet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class HomepageFeaturedBets
{
    public const POOL_SIZE = 10;

    public const DISPLAY_LIMIT = 3;

    /**
     * @return Builder<UserBet>
     */
    public static function latestResolvedQuery(): Builder
    {
        return UserBet::query()
            ->join('events', 'events.id', '=', 'user_bets.event_id')
            ->where('user_bets.status', '<>', UserBet::STATUS_PENDING)
            ->orderByDesc('events.start_time')
            ->orderByDesc('user_bets.id')
            ->select('user_bets.*');
    }

    /**
     * @param  Collection<int, UserBet>  $latestResolved
     * @return Collection<int, UserBet>
     */
    public static function topByWinAmount(Collection $latestResolved, int $displayLimit = self::DISPLAY_LIMIT): Collection
    {
        $selected = collect();
        $seenUserIds = [];

        foreach ($latestResolved->sortByDesc(fn (UserBet $bet) => (float) $bet->real_return) as $bet) {
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
        $pool = self::latestResolvedQuery()
            ->with([
                'user.wallet',
                'event.homeTeam.translations',
                'event.awayTeam.translations',
                'odd.selection.market',
            ])
            ->limit(self::POOL_SIZE)
            ->get();

        return self::topByWinAmount($pool);
    }
}
