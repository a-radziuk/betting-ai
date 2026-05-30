<?php

namespace App\Http\Controllers;

use App\Models\SimpleCryptoPayment;
use App\Services\SimpleCryptoPaymentService;
use App\Support\SimpleCryptoWallets;
use App\Support\SubscriptionPlans;
use App\Support\SubscriptionTerms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SubscriptionCryptoPaymentController extends Controller
{
    public function __construct(
        private readonly SimpleCryptoPaymentService $cryptoPayments,
    ) {}

    public function show(string $plan, string $wallet): View|RedirectResponse
    {
        if (! feature('simple_crypto_payment')) {
            abort(404);
        }

        return $this->renderWalletPage($plan, $wallet);
    }

    public function markPaid(string $plan, string $wallet): RedirectResponse
    {
        if (! feature('simple_crypto_payment')) {
            abort(404);
        }

        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $payment = SimpleCryptoPayment::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $plan)
            ->where('wallet_key', $wallet)
            ->whereIn('status', [
                SimpleCryptoPayment::STATUS_AWAITING_PAYMENT,
                SimpleCryptoPayment::STATUS_PENDING_APPROVAL,
            ])
            ->latest('id')
            ->first();

        if ($payment === null) {
            return redirect()
                ->route('subscribe.payment.crypto', ['plan' => $plan, 'wallet' => $wallet])
                ->withErrors(['payment' => __('Payment not found.')]);
        }

        if ($payment->isPendingApproval()) {
            return redirect()
                ->route('subscribe.payment.crypto', ['plan' => $plan, 'wallet' => $wallet])
                ->with('status', __('Your payment is already awaiting admin approval.'));
        }

        $this->cryptoPayments->markPaid($payment);

        return redirect()
            ->route('subscribe.payment.crypto', ['plan' => $plan, 'wallet' => $wallet])
            ->with('status', __('Thank you. We will verify your transfer and activate your subscription.'));
    }

    private function renderWalletPage(string $plan, string $wallet): View|RedirectResponse
    {
        $planDetails = SubscriptionPlans::find($plan);
        if ($planDetails === null) {
            return redirect()
                ->route('subscribe')
                ->withErrors(['plan' => __('This plan is not available.')]);
        }

        if (SimpleCryptoWallets::find($wallet) === null) {
            return redirect()
                ->route('subscribe.payment', ['plan' => $plan])
                ->withErrors(['wallet' => __('This crypto wallet is not available.')]);
        }

        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        if ($user->hasActiveSeeTipsAccess()) {
            return redirect()
                ->route('subscribe')
                ->with('status', __('You already have access to tips.'));
        }

        if (! SubscriptionTerms::acceptedForPlan($plan)) {
            return redirect()->route('subscribe.terms', ['plan' => $plan]);
        }

        $payment = $this->cryptoPayments->findOrCreatePayment($user, $plan, $wallet);

        return view('subscribe-crypto-payment', [
            'plan' => $planDetails,
            'payment' => $payment,
        ]);
    }
}
