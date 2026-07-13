<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\EventResultService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;

class ResultsImportCommand extends Command
{
    protected $signature = 'results:import
        {filepath : Absolute or project-relative path to a JSON file (as exported by bbc:scrape-results --file)}';

    protected $description = 'Import full-time results from a JSON file and resolve each event (skips already finished events)';

    public function handle(EventResultService $eventResultService): int
    {
        $rawPath = (string) $this->argument('filepath');
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

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('JSON root must be a list (JSON array) of objects.');
        }

        $imported = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($decoded as $row) {
            if (! is_array($row)) {
                $skipped++;
                $this->components->twoColumnDetail('<fg=yellow>Skip</>', 'Row is not an object.');
                continue;
            }

            $eventId = $row['eventId'] ?? null;
            $result = $row['result'] ?? null;

            if (! is_numeric($eventId) || ! is_string($result) || trim($result) === '') {
                $skipped++;
                $this->components->twoColumnDetail('<fg=yellow>Skip</>', 'Row missing eventId/result.');
                continue;
            }

            $eventId = (int) $eventId;
            $result = trim($result);

            $event = Event::query()->find($eventId);
            if ($event === null) {
                $skipped++;
                $this->components->twoColumnDetail('<fg=yellow>Skip</>', "Event {$eventId} not found.");
                continue;
            }

            if ($event->status === Event::STATUS_FINISHED || $event->score !== null) {
                $skipped++;
                $this->components->twoColumnDetail('<fg=yellow>Skip</>', "Event {$eventId} already finished.");
                continue;
            }

            $apply = $eventResultService->applyEventResult($eventId, $result, []);
            if (! $apply['ok']) {
                $failed++;
                $this->components->error("Event {$eventId}: {$apply['message']}");
                continue;
            }

            $imported++;
            $this->components->twoColumnDetail('<fg=green>Settled</>', "Event {$eventId} {$result}");
        }

        $this->newLine();
        $this->components->info("Imported {$imported} event(s); skipped {$skipped}; failed {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveImportPath(string $rawPath): string
    {
        if (is_file($rawPath)) {
            return $rawPath;
        }

        return base_path(trim($rawPath, '/'.DIRECTORY_SEPARATOR));
    }
}

