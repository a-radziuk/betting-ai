<?php

namespace Tests\Unit;

use App\Models\MetamaskPayment;
use App\Models\User;
use App\PayWithMetamask\Jobs\NotifyMetamaskTransactionWatcher;
use App\PayWithMetamask\Services\MetamaskTransactionWatcherClient;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetamaskTransactionWatcherClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_notify_posts_transaction_payload(): void
    {
        config([
            'pay_with_metamask.transaction_watcher_url' => 'http://watcher.test/metamask/transaction',
        ]);

        Http::fake([
            'watcher.test/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $payment = MetamaskPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_MONTH,
            'tx_hash' => '0xeb1d32ba3bb59a8b0c975cb80a96ad5cba095953b4a0a3cd5514230f1179ec78',
            'token' => MetamaskPayment::TOKEN_USDT,
            'amount_cents' => 2999,
            'recipient_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'status' => MetamaskPayment::STATUS_PENDING,
        ]);

        $this->assertTrue(app(MetamaskTransactionWatcherClient::class)->notify($payment));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://watcher.test/metamask/transaction'
                && $request['tx_hash'] === '0xeb1d32ba3bb59a8b0c975cb80a96ad5cba095953b4a0a3cd5514230f1179ec78'
                && $request['token'] === 'USDT'
                && $request['amount_cents'] === 2999;
        });
    }

    public function test_notify_skips_when_url_not_configured(): void
    {
        config(['pay_with_metamask.transaction_watcher_url' => null]);

        Http::fake();

        $user = User::factory()->create();
        $payment = MetamaskPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_WEEK,
            'tx_hash' => '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
            'token' => MetamaskPayment::TOKEN_USDC,
            'amount_cents' => 999,
            'recipient_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'status' => MetamaskPayment::STATUS_PENDING,
        ]);

        $this->assertFalse(app(MetamaskTransactionWatcherClient::class)->notify($payment));

        Http::assertNothingSent();
    }

    public function test_job_notifies_watcher_for_pending_payment(): void
    {
        config([
            'pay_with_metamask.transaction_watcher_url' => 'http://watcher.test/metamask/transaction',
        ]);

        Http::fake([
            'watcher.test/*' => Http::response(null, 204),
        ]);

        $user = User::factory()->create();
        $payment = MetamaskPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_WEEK,
            'tx_hash' => '0xabcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
            'token' => MetamaskPayment::TOKEN_USDC,
            'amount_cents' => 999,
            'recipient_address' => '0x742d35Cc6634C0532925a3b844Bc454e4438f44e',
            'status' => MetamaskPayment::STATUS_PENDING,
        ]);

        (new NotifyMetamaskTransactionWatcher($payment->id))
            ->handle(app(MetamaskTransactionWatcherClient::class));

        Http::assertSent(fn ($request) => $request['token'] === 'USDC' && $request['amount_cents'] === 999);
    }
}
