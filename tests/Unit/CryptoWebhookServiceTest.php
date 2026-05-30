<?php

namespace Tests\Unit;

use App\Services\CryptoWebhookService;
use Tests\TestCase;

class CryptoWebhookServiceTest extends TestCase
{
    public function test_amounts_match_within_three_cent_tolerance(): void
    {
        $service = app(CryptoWebhookService::class);

        $this->assertTrue($service->amountsMatch(2999, 2999));
        $this->assertTrue($service->amountsMatch(2999, 3002));
        $this->assertTrue($service->amountsMatch(2999, 2996));
        $this->assertFalse($service->amountsMatch(2999, 3003));
        $this->assertFalse($service->amountsMatch(2999, 2500));
    }
}
