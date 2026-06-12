<?php

namespace App\Console\Commands;

use App\Services\EventPredictionExportPayload;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use JsonException;

class PredictionsExportCommand extends Command
{
    protected $signature = 'predictions:export';

    protected $description = 'Export all active event predictions to storage/app/predictions_export_<Y-m-d>.json (upload format for /admin/upload-predictions)';

    public function handle(): int
    {
        if (! Schema::hasTable('event_predictions')) {
            $this->components->error('The event_predictions table does not exist.');

            return self::FAILURE;
        }

        $date = Carbon::now(config('app.timezone'))->format('Y-m-d');
        $path = storage_path('app/predictions_export_'.$date.'.json');

        $predictions = EventPredictionExportPayload::activePredictionsQuery()->get();

        $payload = $predictions
            ->map(fn ($prediction) => EventPredictionExportPayload::buildForUpload($prediction))
            ->values()
            ->all();

        try {
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->components->error('Failed to encode export JSON: '.$e->getMessage());

            return self::FAILURE;
        }

        if (file_put_contents($path, $json) === false) {
            $this->components->error("Failed to write {$path}.");

            return self::FAILURE;
        }

        $predictionCount = count($payload);
        $this->components->info("Wrote {$predictionCount} active prediction(s) to {$path}");

        return self::SUCCESS;
    }
}
