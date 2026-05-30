<?php

namespace App\Jobs;

use App\Models\SimpleCryptoPayment;
use App\Services\CryptoWatcherClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyCryptoWatcherOfPayment implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $simpleCryptoPaymentId,
    ) {}

    public function handle(CryptoWatcherClient $cryptoWatcher): void
    {
        $payment = SimpleCryptoPayment::query()->find($this->simpleCryptoPaymentId);

        if ($payment === null || ! $payment->isPendingApproval()) {
            return;
        }

        $cryptoWatcher->notify($payment);
    }
}
