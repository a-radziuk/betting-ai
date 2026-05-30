<?php

namespace App\Jobs;

use App\Models\SimpleCryptoPayment;
use App\Services\SimpleCryptoPaymentTelegramMessage;
use App\Services\TelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifySimpleCryptoPaymentPaid implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $simpleCryptoPaymentId,
    ) {}

    public function handle(
        TelegramNotifier $telegram,
        SimpleCryptoPaymentTelegramMessage $messageBuilder,
    ): void {
        $payment = SimpleCryptoPayment::query()
            ->with('user')
            ->find($this->simpleCryptoPaymentId);

        if ($payment === null || ! $payment->isPendingApproval()) {
            return;
        }

        $telegram->sendMessage($messageBuilder->build($payment));
    }
}
