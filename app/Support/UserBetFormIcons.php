<?php

namespace App\Support;

use App\Models\UserBet;
use Illuminate\Support\Collection;

final class UserBetFormIcons
{
    /**
     * Build W / L / D style segments from up to the five most recent resolved bets (oldest → newest for display, like league form).
     *
     * @param  Collection<int, UserBet>  $bets
     * @return list<array{letter: string, css: string, tooltip: string}>
     */
    public static function fromBets(Collection $bets, bool $excludePending = false): array
    {
        if ($excludePending) {
            $bets = $bets->where('status', '!=', UserBet::STATUS_PENDING)->values();
        }

        $sorted = $bets->sortBy([
            ['resolved_order', 'asc'],
            ['id', 'asc'],
        ])->values();

        if ($sorted->count() > 5) {
            $sorted = $sorted->slice(-5)->values();
        }

        $out = [];
        foreach ($sorted as $bet) {
            $out[] = self::mapBet($bet);
        }

        return $out;
    }

    /**
     * @return array{letter: string, css: string, tooltip: string}
     */
    private static function mapBet(UserBet $bet): array
    {
        $stake = number_format((float) $bet->stake, 2, '.', '');
        $currency = 'EUR';

        return match ($bet->status) {
            UserBet::STATUS_WON => [
                'letter' => 'W',
                'css' => 'w',
                'tooltip' => __('Bet won — stake :stake :currency', ['stake' => $stake, 'currency' => $currency]),
            ],
            UserBet::STATUS_LOST => [
                'letter' => 'L',
                'css' => 'l',
                'tooltip' => __('Bet lost — stake :stake :currency', ['stake' => $stake, 'currency' => $currency]),
            ],
            UserBet::STATUS_VOID => [
                'letter' => 'D',
                'css' => 'd',
                'tooltip' => __('Void — stake :stake :currency returned', ['stake' => $stake, 'currency' => $currency]),
            ],
            UserBet::STATUS_PENDING => [
                'letter' => '—',
                'css' => 'muted',
                'tooltip' => __('Pending — stake :stake :currency', ['stake' => $stake, 'currency' => $currency]),
            ],
            UserBet::STATUS_CANCELLED => [
                'letter' => '—',
                'css' => 'muted',
                'tooltip' => __('Cancelled'),
            ],
            default => [
                'letter' => '—',
                'css' => 'muted',
                'tooltip' => (string) $bet->status,
            ],
        };
    }
}
