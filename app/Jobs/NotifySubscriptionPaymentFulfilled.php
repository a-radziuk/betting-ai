<?php

namespace App\Jobs;

use App\Models\SubscriptionPayment;
use App\Services\SubscriptionPaymentTelegramMessage;
use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifySubscriptionPaymentFulfilled implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $subscriptionPaymentId,
    ) {}

    public function handle(
        TelegramNotifier $telegram,
        SubscriptionPaymentTelegramMessage $messageBuilder,
    ): void {
        $payment = SubscriptionPayment::query()
            ->with('user')
            ->find($this->subscriptionPaymentId);

        if ($payment === null || ! $payment->isFulfilled()) {
            return;
        }

        $telegram->sendMessage($messageBuilder->build($payment));
    }
}
