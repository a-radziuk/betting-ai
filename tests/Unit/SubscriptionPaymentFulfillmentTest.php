<?php

namespace Tests\Unit;

use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SubscriptionPaymentFulfillmentService;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPaymentFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_fulfillment_grants_see_tips_access(): void
    {
        $user = User::factory()->create();
        SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_WEEK,
            'stripe_payment_intent_id' => 'pi_test_123',
            'amount_cents' => 999,
            'currency' => 'eur',
            'status' => SubscriptionPayment::STATUS_PENDING,
        ]);

        $intent = (object) [
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'metadata' => (object) [
                'user_id' => (string) $user->id,
                'plan_id' => SubscriptionPlans::ONE_WEEK,
            ],
        ];

        $service = app(SubscriptionPaymentFulfillmentService::class);
        $this->assertTrue($service->fulfillFromWebhookPaymentIntent($intent));

        $user->refresh();
        $this->assertTrue($user->hasActiveSeeTipsAccess());
        $this->assertNotNull($user->see_tips_expires_at);
    }

    public function test_webhook_fulfillment_rejects_metadata_mismatch(): void
    {
        $user = User::factory()->create();
        SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'stripe_payment_intent_id' => 'pi_test_456',
            'amount_cents' => 2999,
            'currency' => 'eur',
            'status' => SubscriptionPayment::STATUS_PENDING,
        ]);

        $intent = (object) [
            'id' => 'pi_test_456',
            'status' => 'succeeded',
            'metadata' => (object) [
                'user_id' => (string) $user->id,
                'plan_id' => SubscriptionPlans::ONE_WEEK,
            ],
        ];

        $service = app(SubscriptionPaymentFulfillmentService::class);
        $this->assertFalse($service->fulfillFromWebhookPaymentIntent($intent));

        $user->refresh();
        $this->assertFalse($user->hasActiveSeeTipsAccess());
    }

    public function test_webhook_fulfillment_is_idempotent(): void
    {
        $user = User::factory()->create();
        SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'stripe_payment_intent_id' => 'pi_test_789',
            'amount_cents' => 2999,
            'currency' => 'eur',
            'status' => SubscriptionPayment::STATUS_FULFILLED,
            'fulfilled_at' => now(),
        ]);

        $intent = (object) [
            'id' => 'pi_test_789',
            'status' => 'succeeded',
            'metadata' => (object) [
                'user_id' => (string) $user->id,
                'plan_id' => SubscriptionPlans::ONE_MONTH,
            ],
        ];

        $service = app(SubscriptionPaymentFulfillmentService::class);
        $this->assertFalse($service->fulfillFromWebhookPaymentIntent($intent));
    }
}
