<?php

namespace App\PayWithMetamask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\PayWithMetamask\Http\Requests\RecordTransactionRequest;
use App\PayWithMetamask\Services\PaymentRecorder;
use App\PayWithMetamask\Support\Config;
use App\Support\SubscriptionPlans;
use App\Support\SubscriptionTerms;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class RecordTransactionController extends Controller
{
    public function __invoke(
        string $plan,
        RecordTransactionRequest $request,
        PaymentRecorder $recorder,
    ): JsonResponse {
        if (! Config::isReady()) {
            abort(404);
        }

        if (SubscriptionPlans::find($plan) === null) {
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
            $payment = $recorder->record(
                $user,
                $plan,
                $request->validated('tx_hash'),
                $request->validated('token'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => __('Transaction recorded. We will verify your payment shortly.'),
            'payment_id' => $payment->id,
            'tx_hash' => $payment->tx_hash,
        ]);
    }
}
