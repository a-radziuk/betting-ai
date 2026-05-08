<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Console\Command;

class UserResetWalletCommand extends Command
{
    protected $signature = 'user:reset-wallet
        {userId : User primary key}';

    protected $description = 'Reset a user wallet to defaults (balance=1000, total_result=0, amount_in_play=0)';

    public function handle(): int
    {
        $userId = $this->argument('userId');

        if (! User::query()->whereKey($userId)->exists()) {
            $this->components->error('User not found.');

            return self::FAILURE;
        }

        $wallet = UserWallet::query()->firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'start_balance' => 0, 'amount_in_play' => 0, 'total_result' => 0, 'currency' => 'EUR']
        );

        $wallet->update([
            'balance' => 1000,
            'total_result' => 0,
            'amount_in_play' => 0,
        ]);

        $this->components->info("Wallet reset for user {$userId}.");

        return self::SUCCESS;
    }
}
