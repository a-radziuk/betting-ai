<?php

namespace App\Console\Commands;

use App\Models\EventPrediction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PredictionsClearCommand extends Command
{
    protected $signature = 'predictions:clear';

    protected $description = 'Delete all active event predictions';

    public function handle(): int
    {
        if (! Schema::hasTable('event_predictions')) {
            $this->components->error('The event_predictions table does not exist.');

            return self::FAILURE;
        }

        $deleted = EventPrediction::query()->active()->delete();

        if ($deleted === 0) {
            $this->components->info('No active event predictions found.');

            return self::SUCCESS;
        }

        $this->components->info("Deleted {$deleted} active prediction(s).");

        return self::SUCCESS;
    }
}
