<?php

namespace Tests\Unit;

use App\Support\SubscriptionPlans;
use Tests\TestCase;

class SubscriptionPlansTest extends TestCase
{
    public function test_all_returns_only_visible_plans_and_each_is_enabled(): void
    {
        config([
            'subscriptions.plans.one_week.visible' => true,
            'subscriptions.plans.one_month.visible' => false,
            'subscriptions.plans.three_months.visible' => true,
            'subscriptions.plans.one_year.visible' => false,
        ]);

        $plans = SubscriptionPlans::all();

        $this->assertCount(2, $plans);
        $this->assertSame(SubscriptionPlans::ONE_WEEK, $plans[0]['id']);
        $this->assertSame(SubscriptionPlans::THREE_MONTHS, $plans[1]['id']);
        $this->assertTrue($plans[0]['enabled']);
        $this->assertTrue($plans[1]['enabled']);
        $this->assertArrayHasKey('price_label', $plans[0]);
    }

    public function test_is_enabled_only_for_visible_plans(): void
    {
        config(['subscriptions.plans.one_month.visible' => false]);

        $this->assertTrue(SubscriptionPlans::isEnabled(SubscriptionPlans::ONE_WEEK));
        $this->assertFalse(SubscriptionPlans::isEnabled(SubscriptionPlans::ONE_MONTH));
    }

    public function test_format_price_for_common_currencies(): void
    {
        $this->assertSame('€12.50', SubscriptionPlans::formatPrice('12.5', 'EUR'));
        $this->assertSame('$9.00', SubscriptionPlans::formatPrice('9', 'USD'));
        $this->assertSame('10.00 CHF', SubscriptionPlans::formatPrice('10', 'CHF'));
    }
}
