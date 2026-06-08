<?php

namespace App\PayWithMetamask\Services;

use App\Models\MetamaskPayment;
use App\Models\User;
use App\PayWithMetamask\Jobs\NotifyMetamaskTransactionWatcher;
use App\PayWithMetamask\Support\Config;
use App\PayWithMetamask\Support\PlanPayment;
use InvalidArgumentException;

class PaymentRecorder
{
    public function record(User $user, string $planId, string $txHash, string $token): MetamaskPayment
    {
        if (! Config::isReady()) {
            throw new InvalidArgumentException('MetaMask payments are not available.');
        }

        if (! in_array($token, [
            MetamaskPayment::TOKEN_ETH,
            MetamaskPayment::TOKEN_USDT,
            MetamaskPayment::TOKEN_USDC,
        ], true)) {
            throw new InvalidArgumentException('Invalid payment token.');
        }

        $planPayment = PlanPayment::forPlan($planId);

        if ($token === MetamaskPayment::TOKEN_ETH && $planPayment['eth_amount_wei'] === null) {
            throw new InvalidArgumentException('ETH payment is not configured for this plan.');
        }

        if ($token === MetamaskPayment::TOKEN_USDT && Config::usdtContractAddress() === null) {
            throw new InvalidArgumentException('USDT payment is not configured.');
        }

        if ($token === MetamaskPayment::TOKEN_USDC && Config::usdcContractAddress() === null) {
            throw new InvalidArgumentException('USDC payment is not configured.');
        }

        $payment = MetamaskPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'tx_hash' => $txHash,
            'token' => $token,
            'amount_cents' => $planPayment['amount_cents'],
            'recipient_address' => Config::ethereumWallet(),
            'status' => MetamaskPayment::STATUS_PENDING,
        ]);

        NotifyMetamaskTransactionWatcher::dispatch($payment->id);

        return $payment;
    }
}
