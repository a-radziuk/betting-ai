<?php

namespace App\Http\Controllers;

use App\Services\StripeSubscriptionPaymentService;
use App\Support\StripeConfig;
use App\Support\SubscriptionPlans;
use App\Support\SubscriptionTerms;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class SubscriptionStripePaymentIntentController extends Controller
{
    public function __invoke(
        string $plan,
        StripeSubscriptionPaymentService $stripePayments,
    ): JsonResponse {
        if (! feature('subscription_stripe_payments')) {
            abort(404);
        }

        if (! StripeConfig::isConfigured()) {
            abort(503);
        }

        $planDetails = SubscriptionPlans::find($plan);
        if ($planDetails === null) {
            abort(404);
        }

        if (! SubscriptionTerms::acceptedForPlan($plan)) {
            abort(403);
        }

        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        if ($user->hasActiveSeeTipsAccess()) {
            return response()->json([
                'message' => __('You already have access to tips.'),
            ], 422);
        }

        try {
            $result = $stripePayments->createPaymentIntent($user, $planDetails);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }

        return response()->json($result);
    }
}
