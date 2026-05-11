<?php

namespace Tests\Unit;

use App\Support\GuardianFormIcons;
use PHPUnit\Framework\TestCase;

class GuardianFormIconsTest extends TestCase
{
    public function test_parses_concatenated_guardian_form(): void
    {
        $form = 'Lost 1-2 to AFC BournemouthWon 1-0 against NewcastleDrew 2-2 with Everton';
        $segments = GuardianFormIcons::parseSegments($form);

        $this->assertCount(3, $segments);
        $this->assertSame('L', $segments[0]['letter']);
        $this->assertSame('Lost 1-2 to AFC Bournemouth', $segments[0]['tooltip']);
        $this->assertSame('W', $segments[1]['letter']);
        $this->assertSame('Won 1-0 against Newcastle', $segments[1]['tooltip']);
        $this->assertSame('D', $segments[2]['letter']);
        $this->assertSame('Drew 2-2 with Everton', $segments[2]['tooltip']);
    }

    public function test_empty_form_returns_empty_array(): void
    {
        $this->assertSame([], GuardianFormIcons::parseSegments(null));
        $this->assertSame([], GuardianFormIcons::parseSegments(''));
    }
}
