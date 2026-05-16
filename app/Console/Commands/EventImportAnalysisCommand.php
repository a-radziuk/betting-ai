<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventAnalysis;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;

class EventImportAnalysisCommand extends Command
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

    protected $signature = 'event:import-analysis
        {filepath : Absolute or project-relative path to a JSON file (list of analysis objects)}';

    protected $description = 'Import event analyses from a JSON file (array of objects with eventId, likely_outcome, motivation fields, etc.)';

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
                $this->components->warn('Row '.$index.': skipped (not an object).');
                $skipped++;

                continue;
            }

            foreach (self::REQUIRED_ROW_KEYS as $key) {
                if (! array_key_exists($key, $row)) {
                    $this->components->warn('Row '.$index.": skipped (missing \"{$key}\").");
                    $skipped++;

                    continue 2;
                }
            }

            $eventId = (int) $row['eventId'];
            $event = Event::query()->find($eventId);

            if ($event === null) {
                $this->components->warn("Row {$index}: skipped (event {$eventId} not found).");
                $skipped++;

                continue;
            }

            if ($event->status === Event::STATUS_FINISHED) {
                $this->components->warn("Row {$index}: skipped (event {$eventId} is finished).");
                $skipped++;

                continue;
            }

            if (EventAnalysis::query()
                ->where('event_id', $event->id)
                ->where('type', EventAnalysis::TYPE_MANUAL)
                ->exists()) {
                $this->components->warn("Row {$index}: skipped (analysis type ".EventAnalysis::TYPE_MANUAL." already exists for event {$eventId}).");
                $skipped++;

                continue;
            }

            $likelyOutcome = (string) $row['likely_outcome'];
            if (! EventAnalysis::isValidLikelyOutcome($likelyOutcome)) {
                $this->components->warn("Row {$index}: skipped (invalid likely_outcome \"{$likelyOutcome}\").");
                $skipped++;

                continue;
            }

            $scoreFields = [
                'home_motivation' => (int) $row['home_motivation'],
                'away_motivation' => (int) $row['away_motivation'],
                'home_class' => (int) $row['home_class'],
                'away_class' => (int) $row['away_class'],
            ];

            $invalidScoreField = null;
            foreach ($scoreFields as $field => $value) {
                if (! EventAnalysis::isValidStrength($value)) {
                    $invalidScoreField = $field;
                    break;
                }
            }

            if ($invalidScoreField !== null) {
                $this->components->warn("Row {$index}: skipped ({$invalidScoreField} out of range 0–10).");
                $skipped++;

                continue;
            }

            $approximateGoals = (int) $row['approximate_goals'];
            if ($approximateGoals < 0) {
                $this->components->warn("Row {$index}: skipped (approximate_goals must be >= 0).");
                $skipped++;

                continue;
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

            $imported++;
        }

        $this->components->info("Imported {$imported} analysis(es); skipped {$skipped}.");

        return self::SUCCESS;
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

    private function resolveImportPath(string $rawPath): string
    {
        if (is_file($rawPath)) {
            return $rawPath;
        }

        return base_path(trim($rawPath, '/'.DIRECTORY_SEPARATOR));
    }
}
