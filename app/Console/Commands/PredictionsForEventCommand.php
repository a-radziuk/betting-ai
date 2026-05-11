<?php

namespace App\Console\Commands;

use App\Models\EventPrediction;
use App\Services\EventOddsExportPayload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class PredictionsForEventCommand extends Command
{
    protected $signature = 'predictions:for-event
        {eventId : Event primary key}';

    protected $description = 'Request best-odds prediction for a future event and store the API response';

    public function handle(): int
    {
        $eventId = $this->argument('eventId');

        $event = EventOddsExportPayload::findForExport($eventId);

        if ($event === null) {
            $this->components->error('Event not found.');

            return self::FAILURE;
        }

        if ($event->start_time->isPast()) {
            $this->components->warn("Event {$eventId} has already started (start_time is in the past). Skipping.");

            return self::SUCCESS;
        }

        $body = [
            'type' => EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            'event' => EventOddsExportPayload::build($event),
        ];

        $url = config('services.predictions.odds_url');

        try {
            $response = Http::timeout(120)
                ->acceptJson()
                ->asJson()
                ->post($url, $body);
        } catch (Throwable $e) {
            $this->components->error('Prediction API request failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->components->error('Prediction API returned HTTP '.$response->status().': '.$response->body());

            return self::FAILURE;
        }

        $data = $response->json();
        if (! is_array($data)) {
            $this->components->error('Prediction API returned invalid JSON.');

            return self::FAILURE;
        }

        if (! isset($data['oddsId'], $data['bankPercentage'], $data['explanation'])) {
            $this->components->error('Prediction API response missing required fields (oddsId, bankPercentage, explanation).');

            return self::FAILURE;
        }

        $predictionType = EventPrediction::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT;

        DB::transaction(function () use ($event, $data, $predictionType): void {
            EventPrediction::query()
                ->where('event_id', $event->id)
                ->where('prediction_type', $predictionType)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            EventPrediction::query()->create([
                'event_id' => $event->id,
                'prediction_type' => $predictionType,
                'odds_id' => (int) $data['oddsId'],
                'bank_percentage' => (int) $data['bankPercentage'],
                'explanation' => (string) $data['explanation'],
                'is_active' => true,
            ]);
        });

        $this->components->info('Prediction saved for event '.$eventId.'.');

        return self::SUCCESS;
    }
}
