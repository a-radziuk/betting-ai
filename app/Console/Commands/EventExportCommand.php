<?php

namespace App\Console\Commands;

use App\Services\EventOddsExportPayload;
use Illuminate\Console\Command;

class EventExportCommand extends Command
{
    protected $signature = 'event:export
        {eventId : Event primary key}
        {--no-markets= : Comma-separated market types to omit (e.g. CORRECT_SCORE,HANDICAP)}
        {--no-odds : Omit odds from the export}';

    protected $description = 'Export event metadata and odds as JSON to stdout';

    public function handle(): int
    {
        $eventId = $this->argument('eventId');

        $includeOdds = ! $this->option('no-odds');

        $event = EventOddsExportPayload::findForExport($eventId, $includeOdds);

        if ($event === null) {
            $this->components->error('Event not found.');

            return self::FAILURE;
        }

        $excludeMarketTypes = $this->parseExcludedMarketTypesOption();

        $payload = EventOddsExportPayload::build($event, $excludeMarketTypes, $includeOdds);

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function parseExcludedMarketTypesOption(): array
    {
        $raw = $this->option('no-markets');
        if (! is_string($raw)) {
            return [];
        }
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $out = [];
        foreach (explode(',', $raw) as $part) {
            $t = strtoupper(trim($part));
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out, SORT_STRING));
    }
}
