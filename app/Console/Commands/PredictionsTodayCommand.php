<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\RunsPredictionsForScheduledDate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PredictionsTodayCommand extends Command
{
    use RunsPredictionsForScheduledDate;

    protected $signature = 'predictions:today
        {predictionType=1 : Prediction type: 1=best, 2=safest, 3=upset}
        {tournamentId? : Optional tournament primary key; omit for all tournaments}';

    protected $description = 'Run predictions:for-event for each unresolved event scheduled today that has not started yet';

    public function handle(): int
    {
        return $this->handleScheduledPredictions();
    }

    protected function predictionDate(Carbon $now): string
    {
        return $now->format('Y-m-d');
    }

    protected function scheduleDayWord(): string
    {
        return 'today';
    }
}
