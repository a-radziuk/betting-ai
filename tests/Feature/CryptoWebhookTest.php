<?php

namespace Tests\Feature;

use App\Models\SimpleCryptoPayment;
use App\Models\User;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CryptoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function webhookPayload(string $wallet, string $mark, int $transferRaw): array
    {
        return [
            'network' => 'ethereum',
            'wallet' => $wallet,
            'mark' => $mark,
            'transfer' => ['raw' => $transferRaw],
        ];
    }

    private function createPendingApprovalPayment(User $user, int $amountCents = 2999): SimpleCryptoPayment
    {
        return SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'wallet_key' => 'ethereum_usdt',
            'wallet_label' => 'Ethereum USDT',
            'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'payment_code' => 'BETAI-WEBHOOK',
            'amount_cents' => $amountCents,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);
    }

    public function test_webhook_approves_payment_when_amount_matches_within_tolerance(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPendingApprovalPayment($user, 2999);

        $this->postJson('/crypto/webhook', $this->webhookPayload(
            '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'BETAI-WEBHOOK',
            29990000,
        ))->assertOk();

        $payment->refresh();
        $user->refresh();

        $this->assertSame(SimpleCryptoPayment::STATUS_APPROVED, $payment->status);
        $this->assertNotNull($payment->approved_at);
        $this->assertNull($payment->approved_by_user_id);
        $this->assertTrue($user->hasActiveSeeTipsAccess());
        $this->assertSame($this->webhookPayload(
            '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'BETAI-WEBHOOK',
            29990000,
        ), $payment->payment_payload);
    }

    public function test_webhook_approves_when_received_amount_within_three_cents(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPendingApprovalPayment($user, 2999);

        $this->postJson('/crypto/webhook', $this->webhookPayload(
            '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'BETAI-WEBHOOK',
            29980000,
        ))->assertOk();

        $payment->refresh();
        $this->assertSame(SimpleCryptoPayment::STATUS_APPROVED, $payment->status);
    }

    public function test_webhook_marks_pending_admin_review_when_amount_mismatch(): void
    {
        Log::spy();

        $user = User::factory()->create();
        $payment = $this->createPendingApprovalPayment($user, 2999);

        $this->postJson('/crypto/webhook', $this->webhookPayload(
            '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'BETAI-WEBHOOK',
            25000000,
        ))->assertOk();

        $payment->refresh();
        $user->refresh();

        $this->assertSame(SimpleCryptoPayment::STATUS_PENDING_ADMIN_REVIEW, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertFalse($user->hasActiveSeeTipsAccess());
        $this->assertSame($this->webhookPayload(
            '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'BETAI-WEBHOOK',
            25000000,
        ), $payment->payment_payload);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'Crypto webhook amount mismatch');
    }

    public function test_webhook_matches_wallet_case_insensitively_for_ethereum(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPendingApprovalPayment($user, 999);

        $this->postJson('/crypto/webhook', $this->webhookPayload(
            '0x742d35cc6634c0532925a3b844bc454e4438f44e',
            'BETAI-WEBHOOK',
            9990000,
        ))->assertOk();

        $payment->refresh();
        $this->assertSame(SimpleCryptoPayment::STATUS_APPROVED, $payment->status);
    }

    public function test_webhook_logs_when_payment_not_found(): void
    {
        Log::spy();

        $this->postJson('/crypto/webhook', $this->webhookPayload(
            '0x0000000000000000000000000000000000000000',
            'BETAI-MISSING',
            1000000,
        ))->assertOk();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'Crypto webhook payment not found');
    }

    public function test_webhook_logs_invalid_payload(): void
    {
        Log::spy();

        $this->postJson('/crypto/webhook', ['wallet' => '0xabc'])
            ->assertOk();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'Crypto webhook invalid payload');
    }
}
