<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserBet;
use Illuminate\Console\Command;

class ClearUserBetsCommand extends Command
{
    protected $signature = 'bets:clear-user-bets
        {userId? : If set, delete bets only for this user}';

    protected $description = 'Delete all user bets, optionally scoped to one user';

    public function handle(): int
    {
        $userId = $this->argument('userId');

        if ($userId !== null && $userId !== '') {
            if (! User::query()->whereKey($userId)->exists()) {
                $this->components->error('User not found.');

                return self::FAILURE;
            }

            $deleted = UserBet::query()->where('user_id', $userId)->delete();
            $this->components->info("Deleted {$deleted} bet(s) for user {$userId}.");

            return self::SUCCESS;
        }

        $deleted = UserBet::query()->delete();
        $this->components->info("Deleted {$deleted} bet(s) (all users).");

        return self::SUCCESS;
    }
}
