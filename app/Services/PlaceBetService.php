<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Odd;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;

class PlaceBetService
{
    /**
     * @return array{ok: bool, message: string}
     */
    public function placeBet(int|string $userId, int|string $oddId, string|float $stakeRaw): array
    {
        if (! is_numeric($stakeRaw) || (float) $stakeRaw <= 0) {
            return ['ok' => false, 'message' => 'Sum must be a positive number.'];
        }

        $stake = number_format((float) $stakeRaw, 2, '.', '');

        if (! User::query()->whereKey($userId)->exists()) {
            return ['ok' => false, 'message' => 'User not found.'];
        }

        $odd = Odd::query()->with('selection.market')->find($oddId);
        if ($odd === null) {
            return ['ok' => false, 'message' => 'Odd not found.'];
        }

        if ($odd->selection === null || $odd->selection->market === null) {
            return ['ok' => false, 'message' => 'Odd is missing selection or market data.'];
        }

        $eventId = $odd->selection->market->event_id;
        if (! Event::query()->whereKey($eventId)->exists()) {
            return ['ok' => false, 'message' => 'Event for this odd does not exist.'];
        }

        return DB::transaction(function () use ($userId, $odd, $oddId, $eventId, $stake): array {
            $wallet = UserWallet::query()->where('user_id', $userId)->lockForUpdate()->first();
            if ($wallet === null) {
                return ['ok' => false, 'message' => 'User has no wallet.'];
            }

            if (bccomp(
                number_format((float) $wallet->balance, 2, '.', ''),
                $stake,
                2
            ) < 0) {
                return ['ok' => false, 'message' => 'Insufficient wallet balance.'];
            }

            $newBalance = bcsub(
                number_format((float) $wallet->balance, 2, '.', ''),
                $stake,
                2
            );
            $wallet->update(['balance' => $newBalance]);

            $oddsAtBet = number_format((float) $odd->odds, 4, '.', '');
            $potentialReturn = bcmul($stake, $oddsAtBet, 4);
            $potentialReturn = number_format((float) $potentialReturn, 2, '.', '');

            $bet = UserBet::query()->create([
                'user_id' => $userId,
                'event_id' => $eventId,
                'odd_id' => $oddId,
                'stake' => $stake,
                'odds_at_bet' => $oddsAtBet,
                'potential_return' => $potentialReturn,
                'status' => UserBet::STATUS_PENDING,
            ]);

            return [
                'ok' => true,
                'message' => sprintf(
                    'Bet placed. UserBet #%d, stake %s, new balance %s.',
                    $bet->id,
                    $stake,
                    $newBalance
                ),
            ];
        });
    }
}
