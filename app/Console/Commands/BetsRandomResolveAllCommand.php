<?php

namespace App\Console\Commands;

use App\Models\UserBet;
use App\Services\EventResultService;
use Illuminate\Console\Command;

class BetsRandomResolveAllCommand extends Command
{
    protected $signature = 'bets:random-resolve-all';

    protected $description = 'Settle all pending user bets with a random full-time score (0–5 per team) and random first-half score for additional_data';

    public function handle(EventResultService $eventResultService): int
    {
        $eventIds = UserBet::query()
            ->where('status', UserBet::STATUS_PENDING)
            ->distinct()
            ->pluck('event_id')
            ->all();

        if ($eventIds === []) {
            $this->components->info('No pending user bets to resolve.');

            return self::SUCCESS;
        }

        $this->components->info('Resolving '.count($eventIds).' event(s) with random scores (0–5 per team each half)...');

        $rows = [];
        $failures = [];

        foreach ($eventIds as $eventId) {
            $homeFt = random_int(0, 5);
            $awayFt = random_int(0, 5);
            $result = "{$homeFt}:{$awayFt}";

            $homeHt = random_int(0, 5);
            $awayHt = random_int(0, 5);
            /*
            $additionalData = [
                'firstHalf' => "{$homeHt}:{$awayHt}",
                'corners' => random_int(0, 12).':'.random_int(0, 12),
                'yellowCards' => random_int(0, 5).':'.random_int(0, 5),
            ]; */
            $additionalData = [];

            $out = $eventResultService->applyEventResult($eventId, $result, $additionalData);
            if ($out['ok']) {
                $rows[] = [(string) $eventId, $result, '', $out['message']];
            } else {
                $failures[] = "Event {$eventId}: {$out['message']}";
            }
        }

        if ($rows !== []) {
            $this->table(
                ['Event', 'FT', 'HT (extra)', 'Message'],
                $rows
            );
        }

        foreach ($failures as $line) {
            $this->components->error($line);
        }

        if ($failures !== [] && $rows === []) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
