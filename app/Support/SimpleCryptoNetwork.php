<?php

namespace App\Support;

final class SimpleCryptoNetwork
{
    public static function forWalletKey(string $walletKey): ?string
    {
        $network = config("simple_crypto_payment.wallets.{$walletKey}.network");

        if (! is_string($network)) {
            return null;
        }

        $network = trim($network);

        return $network !== '' ? $network : null;
    }
}
