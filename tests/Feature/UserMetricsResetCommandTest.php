<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMetric;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserMetricsResetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_registered(): void
    {
        $this->assertSame(0, Artisan::call('user:metrics-reset'));
    }

    public function test_command_deletes_all_metrics_and_clears_metrics_updated_at(): void
    {
        Carbon::setTestNow('2026-06-10 12:00:00');

        $first = User::factory()->create([
            'metrics_updated_at' => '2026-06-09 12:00:00',
        ]);
        $second = User::factory()->create([
            'metrics_updated_at' => null,
        ]);

        UserMetric::query()->create([
            'user_id' => $first->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 25.00,
        ]);
        UserMetric::query()->create([
            'user_id' => $second->id,
            'type' => UserMetric::TYPE_LAST_10_POSITIVE,
            'amount' => 10.00,
        ]);

        $exit = Artisan::call('user:metrics-reset');
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Deleted 2 metric(s).', $output);
        $this->assertStringContainsString('Reset metrics_updated_at for 1 user(s).', $output);
        $this->assertDatabaseCount('user_metrics', 0);
        $this->assertNull($first->fresh()->metrics_updated_at);
        $this->assertNull($second->fresh()->metrics_updated_at);

        Carbon::setTestNow();
    }
}
