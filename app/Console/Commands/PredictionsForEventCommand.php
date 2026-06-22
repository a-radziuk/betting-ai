<?php

namespace App\Console\Commands;

use App\Models\EventPrediction;
use App\Models\Market;
use App\Services\EventOddsExportPayload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class PredictionsForEventCommand extends Command
{
    protected $signature = 'predictions:for-event
        {eventId : Event primary key}
        {predictionType=1 : Prediction type: 1=best, 2=safest, 3=upset}';

    protected $description = 'Request a prediction for a future event and store the API response';

    public function handle(): int
    {
        $eventId = $this->argument('eventId');
        $predictionType = EventPrediction::predictionTypeFor((int) $this->argument('predictionType'));

        if ($predictionType === null) {
            $this->components->error('predictionType must be 1, 2, or 3.');

            return self::FAILURE;
        }

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
            'type' => $predictionType,
            'event' => EventOddsExportPayload::build($event, [Market::TYPE_CORRECT_SCORE]),
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

        $confidence = null;
        if (array_key_exists('confidence', $data)) {
            if ($data['confidence'] === null) {
                $confidence = null;
            } elseif (is_numeric($data['confidence'])) {
                $confidence = (int) $data['confidence'];
            } else {
                $this->components->error('Prediction API confidence must be numeric or null.');

                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($event, $data, $predictionType, $confidence): void {
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
                'confidence' => $confidence,
                'is_active' => true,
            ]);
        });

        $this->components->info('Prediction saved for event '.$eventId.'.');

        return self::SUCCESS;
    }
}
