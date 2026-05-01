<?php

namespace App\Console\Commands;

use App\Services\PlaceBetService;
use Illuminate\Console\Command;

class PlaceBetCommand extends Command
{
    protected $signature = 'bet:place
        {odd_id : Odd primary key}
        {user_id : User primary key}
        {sum : Stake to debit from the user wallet}';

    protected $description = 'Place a bet: debit wallet and create a UserBet if balance is sufficient';

    public function handle(PlaceBetService $placeBetService): int
    {
        $result = $placeBetService->placeBet(
            $this->argument('user_id'),
            $this->argument('odd_id'),
            $this->argument('sum')
        );

        if (! $result['ok']) {
            $this->components->error($result['message']);

            return self::FAILURE;
        }

        $this->components->info($result['message']);

        return self::SUCCESS;
    }
}
