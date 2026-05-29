<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeSubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'features.subscription_stripe_payments' => true,
            'stripe.key' => 'pk_test_example',
            'stripe.secret' => 'sk_test_example',
        ]);
    }

    public function test_payment_page_shows_stripe_form_when_feature_and_keys_configured(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertSee('id="payment-element"', false)
            ->assertSee('Pay with card', false)
            ->assertDontSee('Payment integration is coming soon', false);
    }

    public function test_payment_page_shows_configuration_message_when_keys_missing(): void
    {
        config([
            'stripe.key' => '',
            'stripe.secret' => '',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe.payment', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertOk()
            ->assertSee('Card payments are not configured', false)
            ->assertDontSee('id="payment-element"', false);
    }

    public function test_stripe_intent_endpoint_requires_feature_flag(): void
    {
        config(['features.subscription_stripe_payments' => false]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('subscribe.payment.stripe-intent', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertNotFound();
    }

    public function test_stripe_intent_endpoint_requires_authentication(): void
    {
        $this->postJson(route('subscribe.payment.stripe-intent', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertUnauthorized();
    }

    public function test_stripe_intent_endpoint_returns_503_when_stripe_not_configured(): void
    {
        config([
            'stripe.key' => '',
            'stripe.secret' => '',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('subscribe.payment.stripe-intent', ['plan' => SubscriptionPlans::ONE_MONTH]))
            ->assertStatus(503);
    }

    public function test_payment_complete_redirects_with_status_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('subscribe.payment.complete', [
                'plan' => SubscriptionPlans::ONE_MONTH,
                'payment_intent' => 'pi_nonexistent',
            ]))
            ->assertRedirect(route('subscribe'))
            ->assertSessionHas('status');
    }
}
