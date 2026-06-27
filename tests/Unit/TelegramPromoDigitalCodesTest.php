<?php

namespace Tests\Unit;

use App\Support\TelegramPromoDigitalCodes;
use PHPUnit\Framework\TestCase;

class TelegramPromoDigitalCodesTest extends TestCase
{
    public function test_detects_start_command(): void
    {
        $this->assertTrue(TelegramPromoDigitalCodes::isStartCommand('/start'));
        $this->assertTrue(TelegramPromoDigitalCodes::isStartCommand('/start@BetGeniousBot'));
        $this->assertFalse(TelegramPromoDigitalCodes::isStartCommand('55501'));
    }

    public function test_matches_hardcoded_codes_inside_message_text(): void
    {
        $this->assertSame('55501', TelegramPromoDigitalCodes::matchInText('code 55501 please'));
        $this->assertSame('55504', TelegramPromoDigitalCodes::matchInText('55504'));
        $this->assertNull(TelegramPromoDigitalCodes::matchInText('99999'));
    }
}
