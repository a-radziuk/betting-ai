<?php

namespace Tests\Unit;

use App\Models\SimpleCryptoPayment;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SimpleCryptoPaymentTelegramMessage;
use App\Services\SubscriptionPaymentTelegramMessage;
use App\Services\TelegramNotifier;
use App\Support\SubscriptionPlans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_message_posts_to_telegram_api(): void
    {
        config([
            'telegram.api_key' => 'unit-test-token',
            'telegram.chat_id' => '-999',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $this->assertTrue(app(TelegramNotifier::class)->sendMessage('Hello from test'));

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/botunit-test-token/sendMessage')
                && $request['chat_id'] === '-999'
                && $request['text'] === 'Hello from test';
        });
    }

    public function test_job_sends_payment_details_via_telegram(): void
    {
        config([
            'telegram.api_key' => 'job-token',
            'telegram.chat_id' => '-100',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create(['email' => 'job@example.com']);
        $payment = SimpleCryptoPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_WEEK,
            'wallet_key' => 'tron_usdt',
            'wallet_label' => 'Tron USDT',
            'wallet_address' => 'TJob',
            'payment_code' => 'BETAI-JOBTEST',
            'amount_cents' => 999,
            'currency' => 'eur',
            'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            'paid_at' => now(),
        ]);

        (new \App\Jobs\NotifySimpleCryptoPaymentPaid($payment->id))->handle(
            app(TelegramNotifier::class),
            app(SimpleCryptoPaymentTelegramMessage::class),
        );

        Http::assertSent(fn ($request) => str_contains($request['text'], 'BETAI-JOBTEST')
            && str_contains($request['text'], 'job@example.com'));
    }

    public function test_stripe_fulfillment_job_sends_payment_details_via_telegram(): void
    {
        config([
            'telegram.api_key' => 'job-token',
            'telegram.chat_id' => '-100',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create(['email' => 'stripe@example.com']);
        $payment = SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => SubscriptionPlans::ONE_WEEK,
            'stripe_payment_intent_id' => 'pi_job_test',
            'amount_cents' => 999,
            'currency' => 'eur',
            'status' => SubscriptionPayment::STATUS_FULFILLED,
            'fulfilled_at' => now(),
        ]);

        (new \App\Jobs\NotifySubscriptionPaymentFulfilled($payment->id))->handle(
            app(TelegramNotifier::class),
            app(SubscriptionPaymentTelegramMessage::class),
        );

        Http::assertSent(fn ($request) => str_contains($request['text'], 'pi_job_test')
            && str_contains($request['text'], 'stripe@example.com'));
    }
}
