<?php

namespace App\Console\Commands;

use App\Services\UserMetricsService;
use Illuminate\Console\Command;

class UserMetricsCommand extends Command
{
    protected $signature = 'user:metrics';

    protected $description = 'Generate user metrics for players with metrics enabled';

    public function handle(UserMetricsService $userMetricsService): int
    {
        $result = $userMetricsService->generate();

        $this->components->info("Deleted {$result['deleted']} metric(s) older than 1 day.");
        $this->components->info("Processed {$result['users_processed']} user(s).");
        $this->components->info("Created {$result['metrics_created']} metric(s).");

        return self::SUCCESS;
    }
}
