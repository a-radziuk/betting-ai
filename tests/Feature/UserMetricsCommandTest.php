<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class UserMetricsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_registered(): void
    {
        $this->assertSame(0, Artisan::call('user:metrics'));
    }

    public function test_command_outputs_summary(): void
    {
        $user = User::factory()->create([
            'is_metrics_available' => true,
        ]);
        $user->wallet->update(['total_result' => 12.5]);

        Artisan::call('user:metrics');

        $output = Artisan::output();

        $this->assertStringContainsString('Deleted 0 metric(s) older than 1 day.', $output);
        $this->assertStringContainsString('Processed 1 user(s).', $output);
        $this->assertStringContainsString('Created 1 metric(s).', $output);

        $this->assertDatabaseHas('user_metrics', [
            'user_id' => $user->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 12.50,
        ]);

        $this->assertNotNull($user->fresh()->metrics_updated_at);
    }
}
