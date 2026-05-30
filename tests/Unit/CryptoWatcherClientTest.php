<?php

namespace Tests\Unit;

use App\Jobs\NotifyCryptoWatcherOfPayment;
use App\Models\SimpleCryptoPayment;
use App\Models\User;
use App\Services\CryptoWatcherClient;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CryptoWatcherClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_posts_json_payload_to_crypto_watcher_url(): void
    {
        config([
            'simple_crypto_payment.crypto_watcher_url' => 'https://watcher.test/hook',
        ]);

        Http::fake([
            'watcher.test/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $payment = SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'wallet_key' => 'ethereum_usdt',
            'wallet_label' => 'Ethereum USDT',
            'wallet_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'payment_code' => 'BETAI-ABCDEFGH',
            'amount_cents' => 2999,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);

        $this->assertTrue(app(CryptoWatcherClient::class)->notify($payment));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://watcher.test/hook'
                && $request->hasHeader('Content-Type', 'application/json')
                && $request['network'] === 'ethereum'
                && $request['wallet'] === '0x742d35Cc6634C0532925a3b844Bc454e4438f44e'
                && $request['mark'] === 'BETAI-ABCDEFGH';
        });
    }

    public function test_notify_skips_when_url_not_configured(): void
    {
        config(['simple_crypto_payment.crypto_watcher_url' => null]);

        Http::fake();

        $user = User::factory()->create();
        $payment = SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'wallet_key' => 'tron_usdt',
            'wallet_label' => 'Tron USDT',
            'wallet_address' => 'TTron123',
            'payment_code' => 'BETAI-TRON01',
            'amount_cents' => 2999,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);

        $this->assertFalse(app(CryptoWatcherClient::class)->notify($payment));

        Http::assertNothingSent();
    }

    public function test_job_notifies_watcher_for_pending_payment(): void
    {
        config([
            'simple_crypto_payment.crypto_watcher_url' => 'https://watcher.test/hook',
        ]);

        Http::fake([
            'watcher.test/*' => Http::response(null, 204),
        ]);

        $user = User::factory()->create();
        $payment = SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_WEEK,
            'wallet_key' => 'tron_usdt',
            'wallet_label' => 'Tron USDT',
            'wallet_address' => 'TJobAddress',
            'payment_code' => 'BETAI-JOBTEST',
            'amount_cents' => 999,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);

        (new NotifyCryptoWatcherOfPayment($payment->id))->handle(app(CryptoWatcherClient::class));

        Http::assertSent(fn ($request) => $request['network'] === 'tron'
            && $request['wallet'] === 'TJobAddress'
            && $request['mark'] === 'BETAI-JOBTEST');
    }
}
