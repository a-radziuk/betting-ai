<?php

namespace Tests\Unit;

use App\Support\PlayerWalletResultChart;
use PHPUnit\Framework\TestCase;

class PlayerWalletResultChartTest extends TestCase
{
    public function test_builds_polyline_for_multiple_values(): void
    {
        $chart = PlayerWalletResultChart::fromValues([0, 10, 5, 20]);

        $this->assertTrue($chart->hasData());
        $this->assertCount(4, $chart->points);
        $this->assertSame(0.0, $chart->min);
        $this->assertSame(20.0, $chart->max);
        $this->assertSame(20.0, $chart->latest);
        $this->assertStringContainsString(' ', $chart->polylinePoints);
        $this->assertSame(20.0, $chart->points[3]['value']);
    }

    public function test_empty_values_produce_no_chart_data(): void
    {
        $chart = PlayerWalletResultChart::fromValues([]);

        $this->assertFalse($chart->hasData());
        $this->assertSame([], $chart->points);
        $this->assertSame('', $chart->polylinePoints);
        $this->assertNull($chart->latest);
    }

    public function test_single_value_produces_one_point(): void
    {
        $chart = PlayerWalletResultChart::fromValues([42.5]);

        $this->assertTrue($chart->hasData());
        $this->assertMatchesRegularExpression('/^\d+\.\d+,\d+\.\d+$/', $chart->polylinePoints);
        $this->assertSame(42.5, $chart->latest);
    }

    public function test_zero_line_when_range_crosses_zero(): void
    {
        $chart = PlayerWalletResultChart::fromValues([-10, 10]);

        $this->assertNotNull($chart->zeroLineY);
    }
}
