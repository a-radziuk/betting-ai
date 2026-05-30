<?php

namespace App\Services;

use App\Models\MetamaskPayment;
use App\Models\SimpleCryptoPayment;
use App\PayWithMetamask\Services\MetamaskPaymentService;
use Illuminate\Support\Facades\Log;

class CryptoWebhookService
{
    private const AMOUNT_TOLERANCE_CENTS = 3;

    public function __construct(
        private readonly SimpleCryptoPaymentService $cryptoPayments,
        private readonly MetamaskPaymentService $metamaskPayments,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $network = strtolower(trim((string) (data_get($payload, 'network') ?? '')));

        if ($network === 'ethereum') {
            $this->handleEthereumMetamask($payload);

            return;
        }

        $this->handleSimpleCrypto($payload);
    }

    public function amountsMatch(int $expectedCents, int $receivedCents): bool
    {
        return abs($expectedCents - $receivedCents) <= self::AMOUNT_TOLERANCE_CENTS;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleEthereumMetamask(array $payload): void
    {
        $txId = $this->resolveMetamaskTxId($payload);
        $transferRaw = data_get($payload, 'transfer.raw');

        $errors = [];
        if ($txId === '') {
            $errors[] = 'missing txId';
        }
        if ($transferRaw === null || ! is_numeric($transferRaw)) {
            $errors[] = 'missing or invalid transfer.raw';
        }

        if ($errors !== []) {
            Log::warning('Crypto webhook invalid ethereum payload', [
                'errors' => $errors,
                'payload' => $payload,
            ]);

            return;
        }

        $receivedCents = (int) round(((float) $transferRaw));
        $payment = $this->findPendingMetamaskPayment($txId);

        if ($payment === null) {
            Log::warning('Crypto webhook metamask payment not found', [
                'tx_id' => $txId,
                'status' => MetamaskPayment::STATUS_PENDING,
                'payload' => $payload,
            ]);

            return;
        }

        if ($this->amountsMatch($payment->amount_cents, $receivedCents)) {
            if ($this->metamaskPayments->approveFromWebhook($payment, $payload)) {
                Log::info('Crypto webhook approved metamask payment', [
                    'payment_id' => $payment->id,
                    'received_cents' => $receivedCents,
                ]);

                return;
            }

            Log::warning('Crypto webhook could not approve metamask payment', [
                'payment_id' => $payment->id,
                'received_cents' => $receivedCents,
                'payload' => $payload,
            ]);

            return;
        }

        Log::warning('Crypto webhook metamask amount mismatch', [
            'payment_id' => $payment->id,
            'expected_cents' => $payment->amount_cents,
            'received_cents' => $receivedCents,
            'transfer_raw' => $transferRaw,
            'tolerance_cents' => self::AMOUNT_TOLERANCE_CENTS,
            'payload' => $payload,
        ]);

        $this->metamaskPayments->markPendingAdminReview($payment, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleSimpleCrypto(array $payload): void
    {
        $wallet = trim((string) (data_get($payload, 'wallet') ?? ''));
        $paymentCode = trim((string) (data_get($payload, 'mark') ?? data_get($payload, 'payment_code') ?? ''));
        $transferRaw = data_get($payload, 'transfer.raw');

        $errors = [];
        if ($wallet === '') {
            $errors[] = 'missing wallet';
        }
        if ($paymentCode === '') {
            $errors[] = 'missing mark (payment_code)';
        }
        if ($transferRaw === null || ! is_numeric($transferRaw)) {
            $errors[] = 'missing or invalid transfer.raw';
        }

        if ($errors !== []) {
            Log::warning('Crypto webhook invalid payload', [
                'errors' => $errors,
                'payload' => $payload,
            ]);

            return;
        }

        $receivedCents = (int) round(((float) $transferRaw) / 10000);
        $payment = $this->findPendingApprovalSimpleCryptoPayment($wallet, $paymentCode);

        if ($payment === null) {
            Log::warning('Crypto webhook payment not found', [
                'wallet' => $wallet,
                'payment_code' => $paymentCode,
                'status' => SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
                'payload' => $payload,
            ]);

            return;
        }

        if ($this->amountsMatch($payment->amount_cents, $receivedCents)) {
            if ($this->cryptoPayments->approveFromWebhook($payment, $payload)) {
                Log::info('Crypto webhook approved payment', [
                    'payment_id' => $payment->id,
                    'received_cents' => $receivedCents,
                ]);

                return;
            }

            Log::warning('Crypto webhook could not approve payment', [
                'payment_id' => $payment->id,
                'received_cents' => $receivedCents,
                'payload' => $payload,
            ]);

            return;
        }

        Log::warning('Crypto webhook amount mismatch', [
            'payment_id' => $payment->id,
            'expected_cents' => $payment->amount_cents,
            'received_cents' => $receivedCents,
            'transfer_raw' => $transferRaw,
            'tolerance_cents' => self::AMOUNT_TOLERANCE_CENTS,
            'payload' => $payload,
        ]);

        $this->cryptoPayments->markPendingAdminReview($payment, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveMetamaskTxId(array $payload): string
    {
        return trim((string) (data_get($payload, 'txId') ?? ''));
    }

    private function findPendingMetamaskPayment(string $txId): ?MetamaskPayment
    {
        return MetamaskPayment::query()
            ->where('status', MetamaskPayment::STATUS_PENDING)
            ->whereRaw('LOWER(tx_hash) = ?', [strtolower($txId)])
            ->latest('id')
            ->first();
    }

    private function findPendingApprovalSimpleCryptoPayment(string $wallet, string $paymentCode): ?SimpleCryptoPayment
    {
        $query = SimpleCryptoPayment::query()
            ->where('payment_code', $paymentCode)
            ->where('status', SimpleCryptoPayment::STATUS_PENDING_APPROVAL);

        if (str_starts_with($wallet, '0x')) {
            $query->whereRaw('LOWER(wallet_address) = ?', [strtolower($wallet)]);
        } else {
            $query->where('wallet_address', $wallet);
        }

        return $query->latest('id')->first();
    }
}
