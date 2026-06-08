<?php

namespace App\PayWithMetamask\Services;

use App\Models\MetamaskPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetamaskTransactionWatcherClient
{
    /**
     * @return array{tx_hash: string, token: string, amount_cents: int}|null
     */
    public function payloadFor(MetamaskPayment $payment): ?array
    {
        $txHash = trim($payment->tx_hash);
        if ($txHash === '') {
            return null;
        }

        return [
            'tx_hash' => $txHash,
            'token' => strtoupper($payment->token),
            'amount_cents' => $payment->amount_cents,
        ];
    }

    public function notify(MetamaskPayment $payment): bool
    {
        $url = config('pay_with_metamask.transaction_watcher_url');
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $payload = $this->payloadFor($payment);
        if ($payload === null) {
            Log::warning('MetaMask transaction watcher skipped: invalid payment payload', [
                'payment_id' => $payment->id,
            ]);

            return false;
        }

        try {
            $response = Http::asJson()
                ->timeout(15)
                ->connectTimeout(5)
                ->post(trim($url), $payload);

            if (! $response->successful()) {
                Log::warning('MetaMask transaction watcher request failed', [
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('MetaMask transaction watcher request exception', [
                'payment_id' => $payment->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
