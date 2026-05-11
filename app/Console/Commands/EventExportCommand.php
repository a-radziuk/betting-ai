<?php

namespace App\Console\Commands;

use App\Services\EventOddsExportPayload;
use Illuminate\Console\Command;

class EventExportCommand extends Command
{
    protected $signature = 'event:export
        {eventId : Event primary key}';

    protected $description = 'Export event metadata and all odds as JSON to stdout';

    public function handle(): int
    {
        $eventId = $this->argument('eventId');

        $event = EventOddsExportPayload::findForExport($eventId);

        if ($event === null) {
            $this->components->error('Event not found.');

            return self::FAILURE;
        }

        $payload = EventOddsExportPayload::build($event);

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
