<?php

namespace Tests\Unit;

use App\Support\SubscriptionPlans;
use PHPUnit\Framework\TestCase;

class SubscriptionPlansTest extends TestCase
{
    public function test_all_returns_three_plans(): void
    {
        $plans = SubscriptionPlans::all();

        $this->assertCount(3, $plans);
        $this->assertSame(SubscriptionPlans::FREE_TRIAL, $plans[0]['id']);
        $this->assertTrue($plans[0]['enabled']);
        $this->assertFalse($plans[1]['enabled']);
        $this->assertFalse($plans[2]['enabled']);
    }

    public function test_is_enabled_only_for_free_trial(): void
    {
        $this->assertTrue(SubscriptionPlans::isEnabled(SubscriptionPlans::FREE_TRIAL));
        $this->assertFalse(SubscriptionPlans::isEnabled(SubscriptionPlans::THREE_MONTHS));
        $this->assertFalse(SubscriptionPlans::isEnabled(SubscriptionPlans::ONE_YEAR));
    }
}
