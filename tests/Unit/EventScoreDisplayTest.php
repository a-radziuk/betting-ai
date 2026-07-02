<?php

namespace Tests\Unit;

use App\Support\EventScoreDisplay;
use Tests\TestCase;

class EventScoreDisplayTest extends TestCase
{
    public function test_formats_base_score_only(): void
    {
        $this->assertSame('2:1', EventScoreDisplay::format('2:1'));
    }

    public function test_formats_aet_and_penalty_suffixes_when_provided(): void
    {
        $this->assertSame(
            '1:1 (aet. 2:2) (pen. 4:3)',
            EventScoreDisplay::format('1:1', '2:2', '4:3')
        );
    }

    public function test_returns_dash_when_base_score_missing(): void
    {
        $this->assertSame('—', EventScoreDisplay::format(null, '2:2', '4:3'));
    }
}
