<?php

namespace App\Console\Commands;

use App\Services\EventResultService;
use Illuminate\Console\Command;
use Throwable;

class BetEventResultCommand extends Command
{
    protected $signature = 'bet:event:result
        {event_id : Event primary key}
        {result : Full-time score e.g. 2:3}
        {additional_data : JSON object e.g. {"corners":"10:12","yellowCards":"3:2","firstHalf":"1:0"}}
        {--score-aet= : Extra-time score e.g. 2:3}
        {--score-pen= : Penalty shootout score e.g. 4:3}';

    protected $description = 'Record event result and settle pending user bets (wallet payout or refund)';

    public function handle(EventResultService $eventResultService): int
    {
        $rawJson = $this->argument('additional_data');

        try {
            $additionalData = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $this->components->error('additional_data must be valid JSON.');

            return self::FAILURE;
        }

        if (! is_array($additionalData)) {
            $this->components->error('additional_data must decode to a JSON object.');

            return self::FAILURE;
        }

        $scoreAet = $this->option('score-aet');
        $scorePen = $this->option('score-pen');

        try {
            if (is_string($scoreAet) && trim($scoreAet) !== '') {
                $eventResultService->parseScoreString(trim($scoreAet));
                $scoreAet = trim($scoreAet);
            } else {
                $scoreAet = null;
            }

            if (is_string($scorePen) && trim($scorePen) !== '') {
                $eventResultService->parseScoreString(trim($scorePen));
                $scorePen = trim($scorePen);
            } else {
                $scorePen = null;
            }
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $result = $eventResultService->applyEventResult(
            $this->argument('event_id'),
            $this->argument('result'),
            $additionalData,
            $scoreAet,
            $scorePen,
        );

        if (! $result['ok']) {
            $this->components->error($result['message']);

            return self::FAILURE;
        }

        $this->components->info($result['message']);

        return self::SUCCESS;
    }
}
