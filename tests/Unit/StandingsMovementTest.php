<?php

namespace Tests\Unit;

use App\Support\StandingsMovement;
use PHPUnit\Framework\TestCase;

class StandingsMovementTest extends TestCase
{
    public function test_all_none_without_previous_rows(): void
    {
        $new = [
            'rows' => [
                ['position' => 1, 'team' => 'A'],
                ['position' => 2, 'team' => 'B'],
            ],
        ];

        $out = StandingsMovement::apply($new, null);
        $this->assertSame('none', $out['rows'][0]['movement']);
        $this->assertSame('none', $out['rows'][1]['movement']);
    }

    public function test_detects_up_and_down(): void
    {
        $previous = [
            'rows' => [
                ['position' => 1, 'team' => 'Alpha'],
                ['position' => 2, 'team' => 'Beta'],
            ],
        ];

        $new = [
            'rows' => [
                ['position' => 1, 'team' => 'Beta'],
                ['position' => 2, 'team' => 'Alpha'],
            ],
        ];

        $out = StandingsMovement::apply($new, $previous);
        $this->assertSame('up', $out['rows'][0]['movement']);
        $this->assertSame('down', $out['rows'][1]['movement']);
    }

    public function test_unknown_team_gets_none(): void
    {
        $previous = [
            'rows' => [
                ['position' => 1, 'team' => 'Only'],
            ],
        ];

        $new = [
            'rows' => [
                ['position' => 1, 'team' => 'Newcomer'],
            ],
        ];

        $out = StandingsMovement::apply($new, $previous);
        $this->assertSame('none', $out['rows'][0]['movement']);
    }
}
