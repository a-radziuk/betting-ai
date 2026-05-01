<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\Selection;
use App\Models\UserBet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearBetsDataCommand extends Command
{
    protected $signature = 'bets:clear';

    protected $description = 'Clear all odds feed data (events, markets, selections, odds)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $stats = DB::transaction(function (): array {
            // user_bets.event_id has an FK to events, so dependent bets must be deleted first.
            $userBets = UserBet::query()->delete();
            $odds = Odd::query()->delete();
            $selections = Selection::query()->delete();
            $markets = Market::query()->delete();
            $events = Event::query()->delete();

            return [
                'events' => $events,
                'markets' => $markets,
                'selections' => $selections,
                'odds' => $odds,
                'user_bets' => $userBets,
            ];
        });

        $this->components->info('Bets data cleared successfully.');
        $this->table(
            ['Table', 'Deleted rows'],
            [
                ['events', $stats['events']],
                ['markets', $stats['markets']],
                ['selections', $stats['selections']],
                ['odds', $stats['odds']],
                ['user_bets (dependent on events)', $stats['user_bets']],
            ]
        );

        return self::SUCCESS;
    }
}
