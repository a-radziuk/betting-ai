<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserBet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class PlayerResolvedBets
{
    /**
     * Resolved bets for the player stats table and CSV (newest settlement first).
     *
     * @return Builder<UserBet>
     */
    public static function listingQuery(User $user): Builder
    {
        return UserBet::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', UserBet::STATUS_PENDING)
            ->orderByResolvedSettlementDesc()
            ->with([
                'event.homeTeam',
                'event.awayTeam',
                'odd.selection.market',
            ]);
    }

    /**
     * @return Builder<UserBet>
     */
    public static function listingQueryForAdmin(?string $search = null): Builder
    {
        $search = trim((string) $search);

        return UserBet::query()
            ->where('status', '!=', UserBet::STATUS_PENDING)
            ->when($search !== '', fn (Builder $query): Builder => self::applyEventNameSearch($query, $search))
            ->orderByResolvedSettlementDesc()
            ->with([
                'user',
                'event.homeTeam',
                'event.awayTeam',
                'event.tournament',
                'odd.selection.market',
            ]);
    }

    /**
     * @param  Builder<UserBet>  $query
     * @return Builder<UserBet>
     */
    public static function applyEventNameSearch(Builder $query, string $search): Builder
    {
        return $query->whereHas('event', function (Builder $eventQuery) use ($search): void {
            $eventQuery->where(function (Builder $match) use ($search): void {
                $match
                    ->whereHas('homeTeam', fn (Builder $teamQuery): Builder => self::applyTeamNameSearch($teamQuery, $search))
                    ->orWhereHas('awayTeam', fn (Builder $teamQuery): Builder => self::applyTeamNameSearch($teamQuery, $search));
            });
        });
    }

    /**
     * @param  Builder<\App\Models\Team>  $query
     * @return Builder<\App\Models\Team>
     */
    private static function applyTeamNameSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $teamQuery) use ($search): void {
            $teamQuery
                ->whereLike('name', '%'.$search.'%')
                ->orWhereLike('display_name', '%'.$search.'%')
                ->orWhereLike('short_name', '%'.$search.'%');
        });
    }

    /**
     * @return Builder<UserBet>
     */
    public static function csvQuery(User $user): Builder
    {
        return self::listingQuery($user);
    }

    /**
     * @return Collection<int, UserBet>
     */
    public static function allForCsv(User $user): Collection
    {
        return self::csvQuery($user)->get();
    }

    public static function eventName(UserBet $bet): string
    {
        $event = $bet->event;
        if ($event === null || $event->homeTeam === null || $event->awayTeam === null) {
            return '—';
        }

        return $event->homeTeam->resolvedDisplayName().' — '.$event->awayTeam->resolvedDisplayName();
    }

    public static function betLabel(UserBet $bet): string
    {
        $selection = $bet->odd?->selection?->name ?? '—';
        $market = $bet->odd?->selection?->market?->type;

        return $market ? "{$selection} ({$market})" : $selection;
    }

    public static function wonLostAmount(UserBet $bet): float
    {
        $stake = (float) $bet->stake;
        $potential = (float) $bet->potential_return;

        return match ($bet->status) {
            UserBet::STATUS_WON => $potential - $stake,
            UserBet::STATUS_LOST => -$stake,
            default => 0.0,
        };
    }

    /**
     * @return list<string|float>
     */
    public static function csvRow(UserBet $bet, bool $forSuperadmin = false): array
    {
        $event = $bet->event;
        $eventDate = $event?->start_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—';
        $score = EventScoreDisplay::forEvent($event);

        $row = [
            $eventDate,
            self::eventName($bet),
            $score,
            self::betLabel($bet),
            number_format((float) $bet->odds_at_bet, 2, '.', ''),
            number_format((float) $bet->stake, 2, '.', ''),
            $bet->status,
            number_format(self::wonLostAmount($bet), 2, '.', ''),
        ];

        if ($forSuperadmin) {
            $row[] = number_format((float) $bet->wallet_total_result, 2, '.', '');
            $row[] = (string) ($bet->resolved_order ?? '');
        }

        return $row;
    }

    /**
     * @return list<string>
     */
    public static function csvHeaders(bool $forSuperadmin = false): array
    {
        $headers = [
            'Date of event',
            'Event',
            'Score',
            'Bet (Selection)',
            'Odd',
            'Amount',
            'Status',
            'Won/Lost',
        ];

        if ($forSuperadmin) {
            $headers[] = 'Wallet total result';
            $headers[] = 'Resolved order';
        }

        return $headers;
    }
}
