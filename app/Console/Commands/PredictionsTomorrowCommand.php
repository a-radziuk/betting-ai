<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPredictionsForScheduledDate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PredictionsTomorrowCommand extends Command
{
    use RunsPredictionsForScheduledDate;

    protected $signature = 'predictions:tomorrow
        {predictionType=1 : Prediction type: 1=best, 2=safest, 3=upset}';

    protected $description = 'Run predictions:for-event for each unresolved event scheduled tomorrow that has not started yet';

    public function handle(): int
    {
        return $this->handleScheduledPredictions();
    }

    protected function predictionDate(Carbon $now): string
    {
        return $now->copy()->addDay()->format('Y-m-d');
    }

    protected function scheduleDayWord(): string
    {
        return 'tomorrow';
    }
}
