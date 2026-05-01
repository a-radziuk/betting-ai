<?php

namespace App\Console\Commands;

use App\Services\StoiximanScraper;
use Illuminate\Console\Command;
use Throwable;

class ScrapeStoiximanCommand extends Command
{
    protected $signature = 'scrape:stoiximan
        {--limit=20 : Number of event pages to attempt}
        {--not-supported-market : Also persist markets whose type is not in Market::SUPPORTED_TYPES (default: skip them)}';

    protected $description = 'Scrape Stoiximan England soccer events and persist events/markets/selections/odds';

    public function handle(StoiximanScraper $scraper): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $notSupportedMarket = (bool) $this->option('not-supported-market');

        $this->components->info("Starting Stoiximan scrape (limit: {$limit})...");

        try {
            $stats = $scraper->scrapeNearestEvents($limit, $notSupportedMarket);
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
