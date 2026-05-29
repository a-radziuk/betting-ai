<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscribePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_page_lists_visible_plans_with_prices(): void
    {
        $html = $this->get(route('subscribe'))
            ->assertOk()
            ->assertSee('1 week', false)
            ->assertSee('1 month', false)
            ->assertSee('3 months', false)
            ->assertSee('1 year', false)
            ->assertSee('€9.99', false)
            ->assertSee('€29.99', false)
            ->assertDontSee('Free trial', false)
            ->assertDontSee('Coming soon', false)
            ->getContent();

        $this->assertStringContainsString('subscribe-plans-grid', $html);
        $this->assertCount(4, SubscriptionPlans::all());
        $this->assertStringContainsString(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_WEEK]), $html);
    }

    public function test_hidden_plans_are_not_listed(): void
    {
        config([
            'subscriptions.plans.one_week.visible' => false,
            'subscriptions.plans.one_month.visible' => false,
            'subscriptions.plans.three_months.visible' => true,
            'subscriptions.plans.one_year.visible' => false,
        ]);

        $this->get(route('subscribe'))
            ->assertOk()
            ->assertSee('3 months', false)
            ->assertDontSee('1 week', false)
            ->assertDontSee('1 month', false)
            ->assertDontSee('1 year', false);
    }

    public function test_subscribe_button_links_to_payment_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe'))
            ->assertOk()
            ->assertSee(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]), false);
    }

    public function test_guest_payment_route_redirects_to_login(): void
    {
        $this->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_payment_stub_when_stripe_feature_disabled(): void
    {
        config(['features.subscription_stripe_payments' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertSee('1 month', false)
            ->assertSee('€29.99', false)
            ->assertSee('Payment integration is coming soon', false);
    }

    public function test_invalid_plan_redirects_to_subscribe(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => 'invalid_plan']))
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasErrors('plan');
    }

    public function test_hidden_plan_cannot_be_opened_on_payment_page(): void
    {
        config(['subscriptions.plans.one_month.visible' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertRedirect(route('subscribe'))
            ->assertSessionHasErrors('plan');
    }

    public function test_user_with_active_tips_sees_disabled_subscribe_buttons(): void
    {
        $user = User::factory()->create();
        $user->grantSeeTipsTrial(1);

        $html = $this->actingAs($user)
            ->get(route('subscribe'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Active', $html);
        $this->assertStringNotContainsString(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]), $html);
    }
}
