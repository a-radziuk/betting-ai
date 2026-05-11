<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Services\StoiximanScraper;
use Illuminate\Console\Command;
use Throwable;

class StoiximanScrapeCommand extends Command
{
    protected $signature = 'stoiximan:scrape
        {tournamentId : Tournament primary key (uses stoiximan_url from this row)}
        {--limit=20 : Number of event pages to attempt}
        {--not-supported-market : Also persist markets whose type is not in Market::SUPPORTED_TYPES (default: skip them)}';

    protected $description = 'Scrape Stoiximan soccer events from a tournament\'s stoiximan_url and persist events/markets/selections/odds';

    public function handle(StoiximanScraper $scraper): int
    {
        $tournamentId = (int) $this->argument('tournamentId');
        $tournament = Tournament::query()->find($tournamentId);

        if ($tournament === null) {
            $this->components->error("Tournament {$tournamentId} not found.");

            return self::FAILURE;
        }

        if (trim((string) $tournament->stoiximan_url) === '') {
            $this->components->error("Tournament {$tournamentId} has no stoiximan_url set.");

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $notSupportedMarket = (bool) $this->option('not-supported-market');

        $this->components->info("Starting Stoiximan scrape for tournament {$tournament->name} / {$tournament->country} (limit: {$limit})...");

        try {
            $stats = $scraper->scrapeNearestEvents($tournament, $limit, $notSupportedMarket);
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
