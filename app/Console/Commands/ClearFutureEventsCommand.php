<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class ClearFutureEventsCommand extends Command
{
    protected $signature = 'bets:clear-events';

    protected $description = 'Clear score and set status to scheduled for all future events (start_time > now)';

    public function handle(): int
    {
        $affected = Event::query()
            ->where('start_time', '>', now())
            ->update([
                'score' => null,
                'status' => Event::STATUS_SCHEDULED,
            ]);

        $this->components->info("Updated {$affected} future event(s): score cleared, status set to ".Event::STATUS_SCHEDULED.'.');

        return self::SUCCESS;
    }
}
