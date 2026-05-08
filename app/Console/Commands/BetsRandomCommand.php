<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Market;
use App\Models\Odd;
use App\Models\User;
use App\Services\PlaceBetService;
use Illuminate\Console\Command;

class BetsRandomCommand extends Command
{
    protected $signature = 'bets:random
        {userId : User primary key}
        {--num-of-bets=20 : How many bets to place}
        {--sum=10 : Stake amount per bet}
        {--market= : Restrict to this market type (e.g. HANDICAP). Omit for any supported type}
        {--event= : Only place bets on odds for this event id}';

    protected $description = 'Place random bets for a user using supported market odds (optional event or market filter)';

    public function handle(PlaceBetService $placeBetService): int
    {
        $userId = $this->argument('userId');
        $numOfBets = max(1, (int) $this->option('num-of-bets'));
        $sum = $this->option('sum');
        $marketFilter = $this->option('market');
        $eventOption = $this->option('event');
        $eventId = null;
        if ($eventOption !== null && $eventOption !== '') {
            $eventId = (int) $eventOption;
            $event = Event::query()->whereKey($eventId)->first();
            if ($event === null) {
                $this->components->error('Event not found.');

                return self::FAILURE;
            }
            if ($event->status !== Event::STATUS_SCHEDULED) {
                $this->components->error('Event must have status scheduled to place bets.');

                return self::FAILURE;
            }
        }

        if (! User::query()->whereKey($userId)->exists()) {
            $this->components->error('User not found.');

            return self::FAILURE;
        }

        $types = Market::SUPPORTED_TYPES;
        if ($marketFilter !== null && $marketFilter !== '') {
            $marketFilter = trim((string) $marketFilter);
            if (! in_array($marketFilter, Market::SUPPORTED_TYPES, true)) {
                $this->components->error(
                    'Invalid market type. Use one of: '.implode(', ', Market::SUPPORTED_TYPES)
                );

                return self::FAILURE;
            }
            $types = [$marketFilter];
        }

        $oddIds = Odd::query()
            ->whereHas('selection', function ($q) use ($types, $eventId): void {
                $q->whereHas('market', function ($m) use ($types, $eventId): void {
                    $m->whereIn('type', $types)
                        ->whereHas('event');
                    if ($eventId !== null) {
                        $m->where('event_id', $eventId);
                    }
                });
            })
            ->pluck('id')
            ->all();

        if ($oddIds === []) {
            $this->components->error('No odds found for the selected filters.');

            return self::FAILURE;
        }

        $placed = 0;
        $failures = [];

        for ($i = 0; $i < $numOfBets; $i++) {
            $oddId = $oddIds[array_rand($oddIds)];
            $result = $placeBetService->placeBet($userId, $oddId, $sum);
            if ($result['ok']) {
                $placed++;
            } else {
                $failures[] = $result['message'];
            }
        }

        $this->components->info("Placed {$placed} of {$numOfBets} bet(s).");
        if ($failures !== []) {
            $this->components->warn('Some bets failed:');
            foreach (array_count_values($failures) as $msg => $count) {
                $this->line("  [{$count}x] {$msg}");
            }
        }

        return $placed > 0 ? self::SUCCESS : self::FAILURE;
    }
}
