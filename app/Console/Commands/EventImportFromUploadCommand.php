<?php

namespace App\Console\Commands;

use App\Services\EventUploadService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;

class EventImportFromUploadCommand extends Command
{
    protected $signature = 'event:import-from-upload
        {filepath : Absolute or project-relative path to a JSON file (same format as event:export-all-for-upload)}';

    protected $description = 'Import events with markets, selections, and odds from a JSON upload file';

    public function handle(EventUploadService $uploadService): int
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
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Invalid JSON: '.$e->getMessage(), 0, $e);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Invalid JSON.');
        }

        $eventCount = $uploadService->import($payload);

        $this->components->info("Uploaded {$eventCount} event(s).");

        return self::SUCCESS;
    }

    private function resolveImportPath(string $rawPath): string
    {
        if (is_file($rawPath)) {
            return $rawPath;
        }

        return base_path(trim($rawPath, '/'.DIRECTORY_SEPARATOR));
    }
}
