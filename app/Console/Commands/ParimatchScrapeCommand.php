<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Services\ParimatchScraper;
use Illuminate\Console\Command;
use Throwable;

class ParimatchScrapeCommand extends Command
{
    protected $signature = 'parimatch:scrape
        {tournamentId : Tournament primary key (uses parimatch_url from this row)}
        {--limit=20 : Number of upcoming event pages to attempt}';

    protected $description = 'Scrape Parimatch soccer events from a tournament\'s parimatch_url and persist events/markets/selections/odds';

    public function handle(ParimatchScraper $scraper): int
    {
        $tournamentId = (int) $this->argument('tournamentId');
        $tournament = Tournament::query()->find($tournamentId);

        if ($tournament === null) {
            $this->components->error("Tournament {$tournamentId} not found.");

            return self::FAILURE;
        }

        if (trim((string) $tournament->parimatch_url) === '') {
            $this->components->error("Tournament {$tournamentId} has no parimatch_url set.");

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));

        $this->components->info("Starting Parimatch scrape for tournament {$tournament->name} (limit: {$limit})...");

        try {
            $stats = $scraper->scrapeNearestEvents($tournament, $limit);
        } catch (Throwable $e) {
            $this->components->error('Scrape failed: '.$e->getMessage());
            $this->newLine();
            $this->line('Ensure Playwright is installed: npm install -D playwright && npx playwright install chromium');

            return self::FAILURE;
        }

        $this->components->info('Scrape completed.');
        $this->table(
            ['Entity', 'Inserted / Updated'],
            [
                ['events', $stats['events']],
                ['markets', $stats['markets']],
                ['selections', $stats['selections']],
                ['odds', $stats['odds']],
            ]
        );

        return self::SUCCESS;
    }
}
