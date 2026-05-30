<?php

namespace Tests\Feature;

use App\Models\MetamaskPayment;
use App\Models\SimpleCryptoPayment;
use App\Models\User;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CryptoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TX_HASH = '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';

    private function simpleCryptoPayload(string $wallet, string $mark, int $transferRaw, string $network = 'tron'): array
    {
        return [
            'network' => $network,
            'wallet' => $wallet,
            'mark' => $mark,
            'transfer' => ['raw' => $transferRaw],
        ];
    }

    private function metamaskPayload(string $txId, int $transferRaw): array
    {
        return [
            'network' => 'ethereum',
            'txId' => $txId,
            'transfer' => ['raw' => $transferRaw],
        ];
    }

    private function createPendingApprovalSimpleCryptoPayment(User $user, int $amountCents = 2999): SimpleCryptoPayment
    {
        return SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'wallet_key' => 'tron_usdt',
            'wallet_label' => 'Tron USDT',
            'wallet_address' => 'TTronWebhookAddr',
            'payment_code' => 'BETAI-WEBHOOK',
            'amount_cents' => $amountCents,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);
    }

    private function createPendingMetamaskPayment(User $user, int $amountCents = 2999): MetamaskPayment
    {
        return MetamaskPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'tx_hash' => self::TX_HASH,
            'token' => MetamaskPayment::TOKEN_USDT,
            'amount_cents' => $amountCents,
            'recipient_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'status' => MetamaskPayment::STATUS_PENDING,
        ]);
    }

    public function test_webhook_approves_simple_crypto_on_tron_network(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPendingApprovalSimpleCryptoPayment($user, 2999);

        $this->postJson('/crypto/webhook', $this->simpleCryptoPayload(
            'TTronWebhookAddr',
            'BETAI-WEBHOOK',
            29990000,
            'tron',
        ))->assertOk();

        $payment->refresh();
        $user->refresh();

        $this->assertSame(SimpleCryptoPayment::STATUS_APPROVED, $payment->status);
        $this->assertTrue($user->hasActiveSeeTipsAccess());
    }

    public function test_webhook_approves_metamask_payment_on_ethereum_network(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPendingMetamaskPayment($user, 2999);

        $this->postJson('/crypto/webhook', $this->metamaskPayload(self::TX_HASH, 29990000))
            ->assertOk();

        $payment->refresh();
        $user->refresh();

        $this->assertSame(MetamaskPayment::STATUS_APPROVED, $payment->status);
        $this->assertNotNull($payment->approved_at);
        $this->assertTrue($user->hasActiveSeeTipsAccess());
        $this->assertSame($this->metamaskPayload(self::TX_HASH, 29990000), $payment->payment_payload);
    }

    public function test_ethereum_webhook_does_not_resolve_simple_crypto_by_payment_code(): void
    {
        $user = User::factory()->create();
        $simpleCrypto = $this->createPendingApprovalSimpleCryptoPayment($user, 2999);
        $simpleCrypto->update([
            'wallet_key' => 'ethereum_usdt',
            'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
        ]);

        $this->postJson('/crypto/webhook', $this->metamaskPayload(self::TX_HASH, 29990000))
            ->assertOk();

        $simpleCrypto->refresh();
        $this->assertSame(SimpleCryptoPayment::STATUS_PENDING_APPROVAL, $simpleCrypto->status);
    }

    public function test_metamask_webhook_marks_pending_admin_review_when_amount_mismatch(): void
    {
        Log::spy();

        $user = User::factory()->create();
        $payment = $this->createPendingMetamaskPayment($user, 2999);

        $this->postJson('/crypto/webhook', $this->metamaskPayload(self::TX_HASH, 25000000))
            ->assertOk();

        $payment->refresh();
        $user->refresh();

        $this->assertSame(MetamaskPayment::STATUS_PENDING_ADMIN_REVIEW, $payment->status);
        $this->assertFalse($user->hasActiveSeeTipsAccess());

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'Crypto webhook metamask amount mismatch');
    }

    public function test_simple_crypto_webhook_marks_pending_admin_review_when_amount_mismatch(): void
    {
        Log::spy();

        $user = User::factory()->create();
        $payment = $this->createPendingApprovalSimpleCryptoPayment($user, 2999);

        $this->postJson('/crypto/webhook', $this->simpleCryptoPayload(
            'TTronWebhookAddr',
            'BETAI-WEBHOOK',
            25000000,
        ))->assertOk();

        $payment->refresh();
        $this->assertSame(SimpleCryptoPayment::STATUS_PENDING_ADMIN_REVIEW, $payment->status);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'Crypto webhook amount mismatch');
    }

    public function test_metamask_webhook_matches_tx_id_case_insensitively(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPendingMetamaskPayment($user, 999);

        $this->postJson('/crypto/webhook', $this->metamaskPayload(
            strtoupper(self::TX_HASH),
            9990000,
        ))->assertOk();

        $payment->refresh();
        $this->assertSame(MetamaskPayment::STATUS_APPROVED, $payment->status);
    }

    public function test_metamask_webhook_does_not_match_by_wallet_when_tx_id_differs(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPendingMetamaskPayment($user, 2999);

        $this->postJson('/crypto/webhook', [
            'network' => 'ethereum',
            'wallet' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'txId' => '0x0000000000000000000000000000000000000000000000000000000000000001',
            'transfer' => ['raw' => 29990000],
        ])->assertOk();

        $payment->refresh();
        $this->assertSame(MetamaskPayment::STATUS_PENDING, $payment->status);
    }

    public function test_metamask_webhook_ignores_mark_without_tx_id(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPendingMetamaskPayment($user, 2999);

        $this->postJson('/crypto/webhook', [
            'network' => 'ethereum',
            'wallet' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'mark' => self::TX_HASH,
            'transfer' => ['raw' => 29990000],
        ])->assertOk();

        $payment->refresh();
        $this->assertSame(MetamaskPayment::STATUS_PENDING, $payment->status);
    }

    public function test_metamask_webhook_logs_when_payment_not_found(): void
    {
        Log::spy();

        $this->postJson('/crypto/webhook', $this->metamaskPayload(
            '0x0000000000000000000000000000000000000000000000000000000000000001',
            1000000,
        ))->assertOk();

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message) => $message === 'Crypto webhook metamask payment not found');
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
