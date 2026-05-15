<?php

namespace App\Console\Commands;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PredictionsTodayCommand extends Command
{
    protected $signature = 'predictions:today';

    protected $description = 'Run predictions:for-event for each unresolved event scheduled today that has not started yet';

    public function handle(): int
    {
        $tz = config('app.timezone');
        $today = Carbon::now($tz)->format('Y-m-d');

        $events = Event::query()
            ->whereNull('score')
            ->where('start_time', '>', now())
            ->whereDate('start_time', $today)
            ->orderBy('start_time')
            ->orderBy('id')
            ->get(['id']);

        if ($events->isEmpty()) {
            $this->components->info('No unresolved upcoming events for today.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($events as $event) {
            $this->components->info("Running predictions:for-event for event {$event->id}...");

            $exitCode = Artisan::call('predictions:for-event', ['eventId' => $event->id]);

            if ($exitCode !== self::SUCCESS) {
                $failed++;
                $output = trim(Artisan::output());
                if ($output !== '') {
                    $this->components->warn($output);
                }
            }
        }

        $total = $events->count();

        if ($failed > 0) {
            $this->components->error("{$failed} of {$total} prediction(s) failed.");

            return self::FAILURE;
        }

        $this->components->info("Predictions completed for {$total} event(s).");

        return self::SUCCESS;
    }
}
