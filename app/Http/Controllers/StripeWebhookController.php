<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionPaymentFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        SubscriptionPaymentFulfillmentService $fulfillment,
    ): Response {
        $secret = config('stripe.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            abort(503);
        }

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return response('Invalid payload', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $fulfillment->fulfillFromWebhookPaymentIntent($event->data->object);
        }

        return response('OK', 200);
    }
}
