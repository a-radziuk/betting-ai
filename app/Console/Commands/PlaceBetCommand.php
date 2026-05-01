<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Odd;
use App\Models\User;
use App\Models\UserBet;
use App\Models\UserWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PlaceBetCommand extends Command
{
    protected $signature = 'bet:place
        {odd_id : Odd primary key}
        {user_id : User primary key}
        {sum : Stake to debit from the user wallet}';

    protected $description = 'Place a bet: debit wallet and create a UserBet if balance is sufficient';

    public function handle(): int
    {
        $oddId = $this->argument('odd_id');
        $userId = $this->argument('user_id');
        $sumRaw = $this->argument('sum');

        if (! is_numeric($sumRaw) || (float) $sumRaw <= 0) {
            $this->components->error('Sum must be a positive number.');

            return self::FAILURE;
        }

        $stake = number_format((float) $sumRaw, 2, '.', '');

        if (! User::query()->whereKey($userId)->exists()) {
            $this->components->error('User not found.');

            return self::FAILURE;
        }

        $odd = Odd::query()->with('selection.market')->find($oddId);
        if ($odd === null) {
            $this->components->error('Odd not found.');

            return self::FAILURE;
        }

        if ($odd->selection === null || $odd->selection->market === null) {
            $this->components->error('Odd is missing selection or market data.');

            return self::FAILURE;
        }

        $eventId = $odd->selection->market->event_id;
        if (! Event::query()->whereKey($eventId)->exists()) {
            $this->components->error('Event for this odd does not exist.');

            return self::FAILURE;
        }

        $result = DB::transaction(function () use ($userId, $odd, $oddId, $eventId, $stake): array {
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

        if (! $result['ok']) {
            $this->components->error($result['message']);

            return self::FAILURE;
        }

        $this->components->info($result['message']);

        return self::SUCCESS;
    }
}
