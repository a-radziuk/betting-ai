<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserBet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class PlayerResolvedBets
{
    /**
     * Resolved bets for the player stats table / CSV (event date newest first).
     *
     * @return Builder<UserBet>
     */
    public static function listingQuery(User $user): Builder
    {
        return UserBet::query()
            ->where('user_bets.user_id', $user->id)
            ->where('user_bets.status', '!=', UserBet::STATUS_PENDING)
            ->join('events', 'events.id', '=', 'user_bets.event_id')
            ->orderByDesc('events.start_time')
            ->orderByDesc('user_bets.id')
            ->select('user_bets.*')
            ->with([
                'event.homeTeam',
                'event.awayTeam',
                'odd.selection.market',
            ]);
    }

    /**
     * @return Collection<int, UserBet>
     */
    public static function allForListing(User $user): Collection
    {
        return self::listingQuery($user)->get();
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
    public static function csvRow(UserBet $bet): array
    {
        $event = $bet->event;
        $eventDate = $event?->start_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—';
        $score = filled($event?->score) ? (string) $event->score : '—';

        return [
            $eventDate,
            self::eventName($bet),
            $score,
            self::betLabel($bet),
            number_format((float) $bet->odds_at_bet, 2, '.', ''),
            number_format((float) $bet->stake, 2, '.', ''),
            $bet->status,
            number_format(self::wonLostAmount($bet), 2, '.', ''),
        ];
    }

    /**
     * @return list<string>
     */
    public static function csvHeaders(): array
    {
        return [
            'Date of event',
            'Event',
            'Score',
            'Bet (Selection)',
            'Odd',
            'Amount',
            'Status',
            'Won/Lost',
        ];
    }
}
