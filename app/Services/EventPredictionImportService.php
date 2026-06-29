<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPrediction;
use App\Models\Odd;
use InvalidArgumentException;

final class EventPredictionImportService
{
    /**
     * @return array{imported: int, skipped: int}
     */
    public function importList(array $decoded): array
    {
        if (! array_is_list($decoded)) {
            throw new InvalidArgumentException('JSON root must be a list (JSON array) of objects.');
        }

        $imported = 0;
        $skipped = 0;

        foreach ($decoded as $row) {
            if ($this->importRow($row)) {
                $imported++;
            } else {
                $skipped++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public function importRow(mixed $row): bool
    {
        if (! is_array($row)) {
            return false;
        }

        foreach (['type', 'description', 'odd_id', 'stake'] as $key) {
            if (! array_key_exists($key, $row)) {
                return false;
            }
        }

        $oddId = (int) $row['odd_id'];
        $odd = Odd::query()->with('selection.market')->find($oddId);

        if ($odd === null || $odd->selection === null || $odd->selection->market === null) {
            return false;
        }

        $eventId = $odd->selection->market->event_id;
        $event = Event::query()->find($eventId);

        if ($event === null) {
            return false;
        }

        if ($event->status === Event::STATUS_FINISHED) {
            return false;
        }

        if (! is_numeric($row['stake'])) {
            return false;
        }

        $stake = (float) $row['stake'];
        if ($stake < 0.01) {
            return false;
        }

        $confidence = null;
        if (array_key_exists('confidence', $row)) {
            if ($row['confidence'] === null) {
                $confidence = null;
            } elseif (is_numeric($row['confidence'])) {
                $confidence = (int) $row['confidence'];
            } else {
                return false;
            }
        }

        EventPrediction::query()->create([
            'event_id' => $event->id,
            'prediction_type' => (string) $row['type'],
            'explanation' => (string) $row['description'],
            'odds_id' => $oddId,
            'bank_percentage' => null,
            'stake' => number_format($stake, 2, '.', ''),
            'confidence' => $confidence,
            'is_active' => true,
        ]);

        return true;
    }
}
