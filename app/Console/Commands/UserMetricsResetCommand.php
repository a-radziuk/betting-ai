<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserMetric;
use Illuminate\Console\Command;

class UserMetricsResetCommand extends Command
{
    protected $signature = 'user:metrics-reset';

    protected $description = 'Delete all user metrics and clear metrics_updated_at for every user';

    public function handle(): int
    {
        $deletedMetrics = UserMetric::query()->delete();
        $resetUsers = User::query()
            ->whereNotNull('metrics_updated_at')
            ->update(['metrics_updated_at' => null]);

        $this->components->info("Deleted {$deletedMetrics} metric(s).");
        $this->components->info("Reset metrics_updated_at for {$resetUsers} user(s).");

        return self::SUCCESS;
    }
}
