<?php

namespace App\Support;

final class SimpleCryptoWallets
{
    /**
     * @return list<array{key: string, label: string, address: string}>
     */
    public static function visible(): array
    {
        if (! feature('simple_crypto_payment')) {
            return [];
        }

        $wallets = [];

        foreach (config('simple_crypto_payment.wallets', []) as $key => $wallet) {
            if (! ($wallet['visible'] ?? false)) {
                continue;
            }

            $address = trim((string) ($wallet['address'] ?? ''));
            if ($address === '') {
                continue;
            }

            $wallets[] = [
                'key' => (string) $key,
                'label' => (string) ($wallet['label'] ?? $key),
                'address' => $address,
            ];
        }

        return $wallets;
    }

    /**
     * @return array{key: string, label: string, address: string}|null
     */
    public static function find(string $key): ?array
    {
        foreach (self::visible() as $wallet) {
            if ($wallet['key'] === $key) {
                return $wallet;
            }
        }

        return null;
    }
}
