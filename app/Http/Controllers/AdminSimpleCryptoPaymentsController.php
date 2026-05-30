<?php

namespace App\Http\Controllers;

use App\Models\SimpleCryptoPayment;
use App\Services\SimpleCryptoPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSimpleCryptoPaymentsController extends Controller
{
    public function __construct(
        private readonly SimpleCryptoPaymentService $cryptoPayments,
    ) {}

    public function index(): View
    {
        if (! feature('simple_crypto_payment')) {
            abort(404);
        }

        $payments = SimpleCryptoPayment::query()
            ->with(['user', 'approvedBy'])
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.simple-crypto-payments', [
            'payments' => $payments,
            'timezone' => config('app.timezone'),
        ]);
    }

    public function approve(SimpleCryptoPayment $payment): RedirectResponse
    {
        if (! feature('simple_crypto_payment')) {
            abort(404);
        }

        $admin = Auth::user();
        if ($admin === null) {
            abort(403);
        }

        if (! $payment->isPendingApproval()) {
            return redirect()
                ->route('admin.simple-crypto-payments')
                ->with('status', __('Only payments awaiting approval can be approved.'));
        }

        if (! $this->cryptoPayments->approve($payment, $admin)) {
            return redirect()
                ->route('admin.simple-crypto-payments')
                ->with('status', __('Could not approve this payment.'));
        }

        return redirect()
            ->route('admin.simple-crypto-payments')
            ->with('status', __('Payment approved and subscription activated.'));
    }
}
