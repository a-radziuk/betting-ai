<?php

namespace App\Services;

use App\Models\UserBet;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;

final class UserBetDeletionService
{
    /**
     * @return array{ok: bool, message: string}
     */
    public function deleteAndRevertWallet(UserBet $bet): array
    {
        return DB::transaction(function () use ($bet): array {
            $bet = UserBet::query()->whereKey($bet->id)->lockForUpdate()->first();
            if ($bet === null) {
                return ['ok' => false, 'message' => 'Bet not found.'];
            }

            $wallet = UserWallet::query()
                ->where('user_id', $bet->user_id)
                ->lockForUpdate()
                ->first();

            if ($wallet === null) {
                return ['ok' => false, 'message' => 'User has no wallet.'];
            }

            $this->revertWalletForBet($wallet, $bet);

            $betId = $bet->id;
            $bet->delete();

            return [
                'ok' => true,
                'message' => __('Bet #:id deleted and wallet balances reverted.', ['id' => $betId]),
            ];
        });
    }

    private function revertWalletForBet(UserWallet $wallet, UserBet $bet): void
    {
        $stake = $this->money($bet->stake);
        $balance = $this->money($wallet->balance);
        $amountInPlay = $this->money($wallet->amount_in_play);
        $totalResult = $this->money($wallet->total_result);

        match ($bet->status) {
            UserBet::STATUS_PENDING => $this->revertPendingBet($stake, $balance, $amountInPlay),
            UserBet::STATUS_WON => $this->revertWonBet($bet, $stake, $balance, $amountInPlay, $totalResult),
            UserBet::STATUS_LOST => $this->revertLostBet($stake, $balance, $amountInPlay, $totalResult),
            UserBet::STATUS_VOID, UserBet::STATUS_CANCELLED => $this->revertRefundedBet($stake, $balance, $amountInPlay),
            default => null,
        };

        $wallet->update([
            'balance' => $balance,
            'amount_in_play' => $amountInPlay,
            'total_result' => $totalResult,
        ]);
    }

    private function revertPendingBet(string $stake, string &$balance, string &$amountInPlay): void
    {
        $balance = bcadd($balance, $stake, 2);
        $amountInPlay = bcsub($amountInPlay, $stake, 2);
    }

    private function revertWonBet(
        UserBet $bet,
        string $stake,
        string &$balance,
        string &$amountInPlay,
        string &$totalResult,
    ): void {
        $potentialReturn = $this->money($bet->potential_return);
        $betReturn = bcsub($potentialReturn, $stake, 2);

        $balance = bcsub($balance, $betReturn, 2);
        $totalResult = bcsub($totalResult, $betReturn, 2);
    }

    private function revertLostBet(string $stake, string &$balance, string &$amountInPlay, string &$totalResult): void
    {
        $balance = bcadd($balance, $stake, 2);
        $totalResult = bcadd($totalResult, $stake, 2);
    }

    private function revertRefundedBet(string $stake, string &$balance, string &$amountInPlay): void
    {
        $balance = bcsub($balance, $stake, 2);
    }

    private function money(string|float|null $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
