<?php

namespace App\Services;

use App\Models\SimpleCryptoPayment;
use Illuminate\Support\Facades\Log;

class CryptoWebhookService
{
    private const AMOUNT_TOLERANCE_CENTS = 3;

    public function __construct(
        private readonly SimpleCryptoPaymentService $cryptoPayments,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
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
        $payment = $this->findPendingApprovalPayment($wallet, $paymentCode);

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

    public function amountsMatch(int $expectedCents, int $receivedCents): bool
    {
        return abs($expectedCents - $receivedCents) <= self::AMOUNT_TOLERANCE_CENTS;
    }

    private function findPendingApprovalPayment(string $wallet, string $paymentCode): ?SimpleCryptoPayment
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
