<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventAnalysis;
use InvalidArgumentException;

final class EventAnalysisImportService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_ROW_KEYS = [
        'eventId',
        'eventName',
        'likely_outcome',
        'approximate_goals',
        'description',
        'home_motivation',
        'away_motivation',
        'home_class',
        'away_class',
    ];

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

        foreach (self::REQUIRED_ROW_KEYS as $key) {
            if (! array_key_exists($key, $row)) {
                return false;
            }
        }

        $eventId = (int) $row['eventId'];
        $event = Event::query()->find($eventId);

        if ($event === null) {
            return false;
        }

        if ($event->status === Event::STATUS_FINISHED) {
            return false;
        }

        if (EventAnalysis::query()
            ->where('event_id', $event->id)
            ->where('type', EventAnalysis::TYPE_MANUAL)
            ->exists()) {
            return false;
        }

        $likelyOutcome = (string) $row['likely_outcome'];
        if (! EventAnalysis::isValidLikelyOutcome($likelyOutcome)) {
            return false;
        }

        $scoreFields = [
            'home_motivation' => (int) $row['home_motivation'],
            'away_motivation' => (int) $row['away_motivation'],
            'home_class' => (int) $row['home_class'],
            'away_class' => (int) $row['away_class'],
        ];

        foreach ($scoreFields as $value) {
            if (! EventAnalysis::isValidStrength($value)) {
                return false;
            }
        }

        $approximateGoals = (int) $row['approximate_goals'];
        if ($approximateGoals < 0) {
            return false;
        }

        EventAnalysis::query()->create([
            'event_id' => $event->id,
            'type' => EventAnalysis::TYPE_MANUAL,
            'strength' => EventAnalysis::STRENGTH_MAX,
            'event_name' => (string) $row['eventName'],
            'likely_outcome' => $likelyOutcome,
            'approximate_goals' => $approximateGoals,
            'description' => (string) $row['description'],
            'home_motivation' => $scoreFields['home_motivation'],
            'away_motivation' => $scoreFields['away_motivation'],
            'home_class' => $scoreFields['home_class'],
            'away_class' => $scoreFields['away_class'],
            'influenced_by' => $this->normalizeStringList($row['influenced_by'] ?? null),
            'influenced_by_event_ids' => $this->normalizeEventIdList($row['influenced_by_event_ids'] ?? null),
        ]);

        return true;
    }

    /**
     * @return list<string>|null
     */
    private function normalizeStringList(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            return null;
        }

        return array_values(array_map(strval(...), $value));
    }

    /**
     * @return list<string>|null
     */
    private function normalizeEventIdList(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            return null;
        }

        return array_values(array_map(
            static fn (mixed $id): string => (string) $id,
            $value,
        ));
    }
}
