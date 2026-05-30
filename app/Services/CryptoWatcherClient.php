<?php

namespace App\Services;

use App\Models\SimpleCryptoPayment;
use App\Support\SimpleCryptoNetwork;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoWatcherClient
{
    /**
     * @return array{network: string, wallet: string, mark: string}|null
     */
    public function payloadFor(SimpleCryptoPayment $payment): ?array
    {
        $network = SimpleCryptoNetwork::forWalletKey($payment->wallet_key);
        if ($network === null) {
            return null;
        }

        $wallet = trim($payment->wallet_address);
        $mark = trim($payment->payment_code);

        if ($wallet === '' || $mark === '') {
            return null;
        }

        return [
            'network' => $network,
            'wallet' => $wallet,
            'mark' => $mark,
        ];
    }

    public function notify(SimpleCryptoPayment $payment): bool
    {
        $url = config('simple_crypto_payment.crypto_watcher_url');
        if (! is_string($url) || trim($url) === '') {
            return false;
        }

        $payload = $this->payloadFor($payment);
        if ($payload === null) {
            Log::warning('Crypto watcher skipped: invalid payment payload', [
                'payment_id' => $payment->id,
                'wallet_key' => $payment->wallet_key,
            ]);

            return false;
        }

        try {
            $response = Http::asJson()
                ->timeout(15)
                ->connectTimeout(5)
                ->post(trim($url), $payload);

            if (! $response->successful()) {
                Log::warning('Crypto watcher request failed', [
                    'payment_id' => $payment->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Crypto watcher request exception', [
                'payment_id' => $payment->id,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
