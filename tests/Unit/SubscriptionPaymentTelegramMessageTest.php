<?php

namespace Tests\Unit;

use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SubscriptionPaymentTelegramMessage;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPaymentTelegramMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_includes_payment_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Alex Payer',
            'email' => 'alex@example.com',
        ]);

        $payment = SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'stripe_payment_intent_id' => 'pi_test_message',
            'amount_cents' => 2999,
            'currency' => 'eur',
            'status' => SubscriptionPayment::STATUS_FULFILLED,
            'fulfilled_at' => now(),
        ]);

        $text = app(SubscriptionPaymentTelegramMessage::class)->build($payment);

        $this->assertStringContainsString('pi_test_message', $text);
        $this->assertStringContainsString('Alex Payer', $text);
        $this->assertStringContainsString('alex@example.com', $text);
        $this->assertStringContainsString('29.99', $text);
        $this->assertStringContainsString('Stripe subscription payment fulfilled', $text);
    }
}
