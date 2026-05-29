<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionPaymentFulfillmentService;
use App\Support\SubscriptionPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionPaymentCompleteController extends Controller
{
    public function __invoke(
        string $plan,
        Request $request,
        SubscriptionPaymentFulfillmentService $fulfillment,
    ): RedirectResponse {
        $planDetails = SubscriptionPlans::find($plan);
        if ($planDetails === null) {
            return redirect()
                ->route('subscribe')
                ->withErrors(['plan' => __('This plan is not available.')]);
        }

        $paymentIntentId = $request->query('payment_intent');
        if (is_string($paymentIntentId) && $paymentIntentId !== '' && feature('subscription_stripe_payments')) {
            $fulfillment->fulfillByPaymentIntentId($paymentIntentId);
        }

        return redirect()
            ->route('subscribe')
            ->with('status', __('Thank you! Your subscription access will be active shortly.'));
    }
}
