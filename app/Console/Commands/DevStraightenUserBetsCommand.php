<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserBet;
use Illuminate\Console\Command;

class DevStraightenUserBetsCommand extends Command
{
    protected $signature = 'dev:straighten-user-bets {userId : User primary key}';

    protected $description = 'Recalculate wallet_total_result on resolved bets as a running sum of real_return (by resolved_order)';

    public function handle(): int
    {
        $userId = (int) $this->argument('userId');

        $user = User::query()->find($userId);
        if ($user === null) {
            $this->components->error("User {$userId} not found.");

            return self::FAILURE;
        }

        $bets = UserBet::query()
            ->where('user_id', $userId)
            ->where('status', '!=', UserBet::STATUS_PENDING)
            ->orderBy('resolved_order')
            ->orderBy('id')
            ->get();

        if ($bets->isEmpty()) {
            $this->components->warn("No resolved bets for user {$userId}.");

            return self::SUCCESS;
        }

        $runningSum = '0.00';
        $updated = 0;

        foreach ($bets as $bet) {
            $runningSum = bcadd(
                $runningSum,
                number_format((float) ($bet->real_return ?? 0), 2, '.', ''),
                2,
            );
            $bet->update(['wallet_total_result' => $runningSum]);
            $updated++;
        }

        $this->components->info("Straightened {$updated} resolved bet(s) for user {$userId} ({$user->name}).");

        return self::SUCCESS;
    }
}
