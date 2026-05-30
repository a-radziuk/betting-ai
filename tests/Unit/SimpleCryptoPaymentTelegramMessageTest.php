<?php

namespace Tests\Unit;

use App\Models\SimpleCryptoPayment;
use App\Models\User;
use App\Services\SimpleCryptoPaymentTelegramMessage;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleCryptoPaymentTelegramMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_includes_payment_details(): void
    {
        config(['features.simple_crypto_payment' => true]);

        $user = User::factory()->create([
            'name' => 'Alex Payer',
            'email' => 'alex@example.com',
        ]);

        $payment = SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'wallet_key' => 'tron_usdt',
            'wallet_label' => 'Tron USDT',
            'wallet_address' => 'TAddr123',
            'payment_code' => 'BETAI-MSGTEST',
            'amount_cents' => 2999,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);

        $text = app(SimpleCryptoPaymentTelegramMessage::class)->build($payment);

        $this->assertStringContainsString('BETAI-MSGTEST', $text);
        $this->assertStringContainsString('Alex Payer', $text);
        $this->assertStringContainsString('alex@example.com', $text);
        $this->assertStringContainsString('Tron USDT', $text);
        $this->assertStringContainsString('TAddr123', $text);
        $this->assertStringContainsString('29.99', $text);
    }
}
