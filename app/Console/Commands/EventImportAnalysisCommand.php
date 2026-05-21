<?php

namespace App\Console\Commands;

use App\Services\EventAnalysisImportService;
use Illuminate\Console\Command;
use InvalidArgumentException;
use JsonException;

class EventImportAnalysisCommand extends Command
{
    protected $signature = 'event:import-analysis
        {filepath : Absolute or project-relative path to a JSON file (list of analysis objects)}';

    protected $description = 'Import event analyses from a JSON file (array of objects with eventId, likely_outcome, motivation fields, etc.)';

    public function handle(EventAnalysisImportService $importService): int
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

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('JSON root must be a list (JSON array) of objects.');
        }

        $result = $importService->importList($decoded);

        $this->components->info("Imported {$result['imported']} analysis(es); skipped {$result['skipped']}.");

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
