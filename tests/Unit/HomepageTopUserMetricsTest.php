<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserMetric;
use App\Support\HomepageTopUserMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTopUserMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_top_three_metrics_for_different_users_ordered_by_amount(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $third = User::factory()->create();
        $fourth = User::factory()->create();

        UserMetric::query()->create([
            'user_id' => $first->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 100.00,
        ]);
        UserMetric::query()->create([
            'user_id' => $second->id,
            'type' => UserMetric::TYPE_LAST_10_POSITIVE,
            'amount' => 300.00,
        ]);
        UserMetric::query()->create([
            'user_id' => $third->id,
            'type' => UserMetric::TYPE_LAST_20_POSITIVE,
            'amount' => 200.00,
        ]);
        UserMetric::query()->create([
            'user_id' => $fourth->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 50.00,
        ]);

        $metrics = HomepageTopUserMetrics::forHomepage();

        $this->assertCount(3, $metrics);
        $this->assertSame([$second->id, $third->id, $first->id], $metrics->pluck('user_id')->all());
        $this->assertSame(['300.00', '200.00', '100.00'], $metrics->pluck('amount')->map(fn ($amount) => number_format((float) $amount, 2))->all());
    }

    public function test_returns_empty_collection_when_fewer_than_three_users_have_metrics(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        UserMetric::query()->create([
            'user_id' => $first->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 100.00,
        ]);
        UserMetric::query()->create([
            'user_id' => $second->id,
            'type' => UserMetric::TYPE_TOTAL_RESULT_POSITIVE,
            'amount' => 200.00,
        ]);

        $this->assertTrue(HomepageTopUserMetrics::forHomepage()->isEmpty());
    }
}
