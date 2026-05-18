<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventPrediction;
use App\Models\Odd;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;

class PredictionsImportCommand extends Command
{
    protected $signature = 'predictions:import
        {filepath : Absolute or project-relative path to a JSON file (list of objects)}';

    protected $description = 'Import event predictions from a JSON file (array of objects with type, description, odd_id, stake, optional confidence)';

    public function handle(): int
    {
        $rawPath = $this->argument('filepath');
        $path = $this->resolveImportPath($rawPath);

        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("File not found or not readable: {$rawPath}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new InvalidArgumentException("Could not read file: {$path}");
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON: '.$e->getMessage(), 0, $e);
        }

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            throw new InvalidArgumentException('JSON root must be a list (JSON array) of objects.');
        }

        $imported = 0;
        $skipped = 0;

        foreach ($decoded as $index => $row) {
            if (! is_array($row)) {
                $this->components->warn('Row '.(is_int($index) ? (string) $index : '').': skipped (not an object).');
                $skipped++;

                continue;
            }

            foreach (['type', 'description', 'odd_id', 'stake'] as $key) {
                if (! array_key_exists($key, $row)) {
                    $this->components->warn('Row '.$index.": skipped (missing \"{$key}\").");
                    $skipped++;

                    continue 2;
                }
            }

            $oddId = (int) $row['odd_id'];
            $odd = Odd::query()->with('selection.market')->find($oddId);

            if ($odd === null || $odd->selection === null || $odd->selection->market === null) {
                $this->components->warn("Row {$index}: skipped (odd {$oddId} not found or incomplete chain).");
                $skipped++;

                continue;
            }

            $eventId = $odd->selection->market->event_id;
            $event = Event::query()->find($eventId);

            if ($event === null) {
                $this->components->warn("Row {$index}: skipped (event {$eventId} not found).");
                $skipped++;

                continue;
            }

            if ($event->status === Event::STATUS_FINISHED) {
                $this->components->warn("Row {$index}: skipped (event {$event->id} is finished).");
                $skipped++;

                continue;
            }

            $bankPercentage = (int) round((float) ($row['stake'] / 1000) * 100);
            $bankPercentage = max(0, min(65535, $bankPercentage));

            $confidence = null;
            if (array_key_exists('confidence', $row)) {
                if ($row['confidence'] === null) {
                    $confidence = null;
                } elseif (is_numeric($row['confidence'])) {
                    $confidence = (int) $row['confidence'];
                } else {
                    $this->components->warn("Row {$index}: skipped (confidence must be numeric or null).");
                    $skipped++;

                    continue;
                }
            }

            EventPrediction::query()->create([
                'event_id' => $event->id,
                'prediction_type' => (string) $row['type'],
                'explanation' => (string) $row['description'],
                'odds_id' => $oddId,
                'bank_percentage' => $bankPercentage,
                'confidence' => $confidence,
                'is_active' => true,
            ]);

            $imported++;
        }

        $this->components->info("Imported {$imported} prediction(s); skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function resolveImportPath(string $rawPath): string
    {
        if (is_file($rawPath)) {
            return $rawPath;
        }

        $candidate = base_path(trim($rawPath, '/'.DIRECTORY_SEPARATOR));

        return $candidate;
    }
}
