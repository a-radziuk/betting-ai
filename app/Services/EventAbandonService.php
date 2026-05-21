<?php

namespace App\Services;

use App\Models\Event;
use App\Models\UserBet;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;

final class EventAbandonService
{
    /**
     * @return array{ok: bool, message: string}
     */
    public function abandon(int|string $eventId, ?string $adminComment = null): array
    {
        $event = Event::query()->find($eventId);
        if ($event === null) {
            return ['ok' => false, 'message' => 'Event not found.'];
        }

        if ($event->status === Event::STATUS_FINISHED) {
            return ['ok' => false, 'message' => 'Event is already resolved.'];
        }

        return DB::transaction(function () use ($eventId, $adminComment): array {
            $comment = 'ABANDONED';
            $trimmed = is_string($adminComment) ? trim($adminComment) : '';
            if ($trimmed !== '') {
                $comment .= ' '.$trimmed;
            }

            Event::query()->whereKey($eventId)->update([
                'status' => Event::STATUS_FINISHED,
                'comment' => $comment,
            ]);

            $bets = UserBet::query()
                ->where('event_id', $eventId)
                ->where('status', UserBet::STATUS_PENDING)
                ->orderBy('id')
                ->get();

            foreach ($bets as $bet) {
                $resolvedOrder = $this->nextResolvedOrder($bet);
                $this->cancelAndRefundBet($bet);
                $bet->update(['resolved_order' => $resolvedOrder]);
            }

            return [
                'ok' => true,
                'message' => sprintf(
                    'Event abandoned. Cancelled and refunded %d pending bet(s).',
                    $bets->count()
                ),
            ];
        });
    }

    private function nextResolvedOrder(UserBet $bet): int
    {
        $previousBet = UserBet::query()
            ->where('user_id', $bet->user_id)
            ->whereKeyNot($bet->id)
            ->orderByDesc('resolved_order')
            ->orderByDesc('id')
            ->first();

        return $previousBet !== null
            ? $previousBet->resolved_order + 1
            : 1;
    }

    private function cancelAndRefundBet(UserBet $bet): void
    {
        $wallet = UserWallet::query()->where('user_id', $bet->user_id)->lockForUpdate()->first();
        if ($wallet !== null) {
            $newBalance = bcadd(
                number_format((float) $wallet->balance, 2, '.', ''),
                number_format((float) $bet->stake, 2, '.', ''),
                2
            );
            $newAmountInPlay = bcsub(
                number_format((float) $wallet->amount_in_play, 2, '.', ''),
                number_format((float) $bet->stake, 2, '.', ''),
                2
            );
            $wallet->update(['balance' => $newBalance, 'amount_in_play' => $newAmountInPlay]);
        }

        $bet->update([
            'status' => UserBet::STATUS_CANCELLED,
            'wallet_total_result' => $wallet?->total_result,
        ]);
    }
}
