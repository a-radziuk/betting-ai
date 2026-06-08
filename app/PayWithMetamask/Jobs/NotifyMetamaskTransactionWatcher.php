<?php

namespace App\PayWithMetamask\Jobs;

use App\Models\MetamaskPayment;
use App\PayWithMetamask\Services\MetamaskTransactionWatcherClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifyMetamaskTransactionWatcher implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $metamaskPaymentId,
    ) {}

    public function handle(MetamaskTransactionWatcherClient $watcher): void
    {
        $payment = MetamaskPayment::query()->find($this->metamaskPaymentId);

        if ($payment === null || ! $payment->isPending()) {
            return;
        }

        $watcher->notify($payment);
    }
}
