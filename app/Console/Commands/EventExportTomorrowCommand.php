<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ExportsScheduledEventsForDate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EventExportTomorrowCommand extends Command
{
    use ExportsScheduledEventsForDate;

    protected $signature = 'event:export-tomorrow
        {tournamentId? : Optional tournament primary key; omit for all tournaments}
        {--no-markets= : Comma-separated market types to omit from each event export (passed to event:export)}
        {--no-odds : Omit odds from each event export (passed to event:export)}
        {--full : Also write <date>.txt with the same JSON plus bet-finder instructions}';

    protected $description = 'Run event:export for each event scheduled tomorrow (app timezone) that has not started yet; write JSON (events grouped by tournament) to storage/app/<Y-m-d>.json; with --full, also write <Y-m-d>.txt (same JSON plus prompt)';

    public function handle(): int
    {
        return $this->handleScheduledEventsExport();
    }

    protected function exportDate(Carbon $now): string
    {
        return $now->copy()->addDay()->format('Y-m-d');
    }

    protected function exportDayWord(): string
    {
        return 'tomorrow';
    }
}
