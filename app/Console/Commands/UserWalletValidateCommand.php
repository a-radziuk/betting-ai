<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserBet;
use App\Models\UserWallet;
use Illuminate\Console\Command;

class UserWalletValidateCommand extends Command
{
    protected $signature = 'user:wallet-validate
        {userId : User primary key}';

    protected $description = 'Validate wallet aggregates against user bets (amount_in_play, total_result)';

    public function handle(): int
    {
        $userId = $this->argument('userId');

        if (! User::query()->whereKey($userId)->exists()) {
            $this->components->error('User not found.');

            return self::FAILURE;
        }

        $wallet = UserWallet::query()->where('user_id', $userId)->first();
        if ($wallet === null) {
            $this->components->error('User wallet not found.');

            return self::FAILURE;
        }

        $expectedInPlay = (string) UserBet::query()
            ->where('user_id', $userId)
            ->where('status', UserBet::STATUS_PENDING)
            ->sum('stake');
        $expectedInPlay = number_format((float) $expectedInPlay, 2, '.', '');

        $expectedTotalResult = (string) UserBet::query()
            ->where('user_id', $userId)
            ->where('status', '!=', UserBet::STATUS_PENDING)
            ->sum('real_return');
        $expectedTotalResult = number_format((float) $expectedTotalResult, 2, '.', '');

        $actualInPlay = number_format((float) $wallet->amount_in_play, 2, '.', '');
        $actualTotalResult = number_format((float) $wallet->total_result, 2, '.', '');

        $okInPlay = bccomp($actualInPlay, $expectedInPlay, 2) === 0;
        $okTotalResult = bccomp($actualTotalResult, $expectedTotalResult, 2) === 0;

        $this->components->info("Wallet validation for user {$userId}:");
        $this->components->twoColumnDetail(
            $okInPlay ? '<fg=green>OK</>' : '<fg=red>MISMATCH</>',
            "amount_in_play: actual {$actualInPlay}, expected {$expectedInPlay}"
        );
        $this->components->twoColumnDetail(
            $okTotalResult ? '<fg=green>OK</>' : '<fg=red>MISMATCH</>',
            "total_result: actual {$actualTotalResult}, expected {$expectedTotalResult}"
        );

        return ($okInPlay && $okTotalResult) ? self::SUCCESS : self::FAILURE;
    }
}
