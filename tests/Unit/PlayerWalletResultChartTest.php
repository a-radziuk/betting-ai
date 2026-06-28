<?php

namespace Tests\Unit;

use App\Support\PlayerWalletResultChart;
use PHPUnit\Framework\TestCase;

class PlayerWalletResultChartTest extends TestCase
{
    public function test_builds_polyline_with_origin_point_first(): void
    {
        $chart = PlayerWalletResultChart::fromValues([10, 5, 20]);

        $this->assertTrue($chart->hasData());
        $this->assertCount(4, $chart->points);
        $this->assertTrue($chart->points[0]['isOrigin']);
        $this->assertSame(0.0, $chart->points[0]['value']);
        $this->assertSame(10.0, $chart->points[1]['value']);
        $this->assertFalse($chart->points[3]['isOrigin']);
        $this->assertSame(20.0, $chart->latest);
        $this->assertSame(20.0, $chart->trendDelta);
        $this->assertStringContainsString(' ', $chart->polylinePoints);
    }

    public function test_empty_values_produce_no_chart_data(): void
    {
        $chart = PlayerWalletResultChart::fromValues([]);

        $this->assertFalse($chart->hasData());
        $this->assertSame([], $chart->points);
        $this->assertSame('', $chart->polylinePoints);
        $this->assertNull($chart->latest);
        $this->assertNull($chart->trendDelta);
    }

    public function test_single_bet_includes_origin_and_bet_points(): void
    {
        $chart = PlayerWalletResultChart::fromValues([42.5]);

        $this->assertTrue($chart->hasData());
        $this->assertCount(2, $chart->points);
        $this->assertTrue($chart->points[0]['isOrigin']);
        $this->assertSame(0.0, $chart->points[0]['value']);
        $this->assertSame(42.5, $chart->points[1]['value']);
        $this->assertSame(42.5, $chart->latest);
        $this->assertSame(42.5, $chart->trendDelta);
    }

    public function test_zero_line_when_range_crosses_zero(): void
    {
        $chart = PlayerWalletResultChart::fromValues([-10, 10]);

        $this->assertNotNull($chart->zeroLineY);
        $this->assertTrue($chart->points[0]['isOrigin']);
    }

    public function test_can_start_at_first_value_without_zero_origin(): void
    {
        $chart = PlayerWalletResultChart::fromValues([100.0, 125.0, 140.0], startAtZero: false);

        $this->assertCount(3, $chart->points);
        $this->assertFalse($chart->points[0]['isOrigin']);
        $this->assertSame(100.0, $chart->points[0]['value']);
        $this->assertSame(140.0, $chart->latest);
        $this->assertSame(40.0, $chart->trendDelta);
    }

    public function test_attaches_dates_to_points_and_axis_labels(): void
    {
        $chart = PlayerWalletResultChart::fromValues(
            [10.0, 20.0, 30.0],
            dates: ['1 Jan 2026', '2 Jan 2026', '3 Jan 2026'],
            axisDates: ['1 Jan', '2 Jan', '3 Jan'],
        );

        $this->assertNull($chart->points[0]['date']);
        $this->assertSame('1 Jan 2026', $chart->points[1]['date']);
        $this->assertSame('3 Jan 2026', $chart->points[3]['date']);
        $this->assertSame('1 Jan', $chart->points[1]['axisDate']);

        $labels = $chart->axisDateLabels();
        $this->assertCount(3, $labels);
        $this->assertSame('1 Jan', $labels[0]['date']);
        $this->assertSame('start', $labels[0]['align']);
        $this->assertSame('end', $labels[2]['align']);
    }
}
