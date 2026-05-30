<?php

namespace App\PayWithMetamask\Support;

final class Config
{
    public static function isEnabled(): bool
    {
        return feature('pay_with_metamask');
    }

    public static function isConfigured(): bool
    {
        return self::ethereumWallet() !== ''
            && (self::usdtContractAddress() !== null || self::usdcContractAddress() !== null);
    }

    public static function isReady(): bool
    {
        return self::isEnabled() && self::isConfigured();
    }

    public static function ethereumWallet(): string
    {
        return trim((string) config('pay_with_metamask.ethereum_wallet'));
    }

    public static function usdtContractAddress(): ?string
    {
        $address = trim((string) config('pay_with_metamask.usdt_contract_address'));

        return $address !== '' ? $address : null;
    }

    public static function usdcContractAddress(): ?string
    {
        $address = trim((string) config('pay_with_metamask.usdc_contract_address'));

        return $address !== '' ? $address : null;
    }

    public static function chainId(): int
    {
        return (int) config('pay_with_metamask.chain_id', 1);
    }
}
