<?php

namespace App\Console\Commands\Concerns;

use App\Models\Event;
use App\Models\EventPrediction;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

trait RunsPredictionsForScheduledDate
{
    abstract protected function predictionDate(Carbon $now): string;

    abstract protected function scheduleDayWord(): string;

    public function handleScheduledPredictions(): int
    {
        $predictionTypeKey = (int) $this->argument('predictionType');

        if (EventPrediction::predictionTypeFor($predictionTypeKey) === null) {
            $this->components->error('predictionType must be 1, 2, or 3.');

            return self::FAILURE;
        }

        $tournamentIdArg = $this->argument('tournamentId');
        $tournamentId = null;

        if ($tournamentIdArg !== null && $tournamentIdArg !== '') {
            $tournamentId = (int) $tournamentIdArg;
            if (! Schema::hasTable('tournaments') || ! Tournament::query()->whereKey($tournamentId)->exists()) {
                $this->components->error("Tournament [{$tournamentId}] not found.");

                return self::FAILURE;
            }
        }

        $tz = config('app.timezone');
        $date = $this->predictionDate(Carbon::now($tz));
        $dayWord = $this->scheduleDayWord();

        $query = Event::query()
            ->whereNull('score')
            ->where('start_time', '>', now())
            ->whereDate('start_time', $date);

        if ($tournamentId !== null && Schema::hasColumn('events', 'tournament_id')) {
            $query->where('tournament_id', $tournamentId);
        }

        $events = $query
            ->orderBy('start_time')
            ->orderBy('id')
            ->get(['id']);

        if ($events->isEmpty()) {
            $scope = $tournamentId !== null ? " for tournament {$tournamentId}" : '';
            $this->components->info("No unresolved upcoming events for {$dayWord}{$scope}.");

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($events as $event) {
            $this->components->info("Running predictions:for-event for event {$event->id}...");

            $exitCode = Artisan::call('predictions:for-event', [
                'eventId' => $event->id,
                'predictionType' => $predictionTypeKey,
            ]);

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

        $scope = $tournamentId !== null ? " for tournament {$tournamentId}" : '';
        $this->components->info("Predictions completed for {$total} event(s){$scope}.");

        return self::SUCCESS;
    }
}
