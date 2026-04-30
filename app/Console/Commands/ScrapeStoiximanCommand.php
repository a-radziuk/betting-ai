<?php

namespace App\Console\Commands;

use App\Services\StoiximanScraper;
use Illuminate\Console\Command;
use Throwable;

class ScrapeStoiximanCommand extends Command
{
    protected $signature = 'scrape:stoiximan {--limit=20 : Number of event pages to attempt}';

    protected $description = 'Scrape Stoiximan England soccer events and persist events/markets/selections/odds';

    public function handle(StoiximanScraper $scraper): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $this->components->info("Starting Stoiximan scrape (limit: {$limit})...");

        try {
            $stats = $scraper->scrapeNearestEvents($limit);
        } catch (Throwable $e) {
            $this->components->error('Scrape failed: '.$e->getMessage());
            $this->newLine();
            $this->line('If Stoiximan blocks HTTP clients (Cloudflare), run this command from a browser-capable environment.');

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
