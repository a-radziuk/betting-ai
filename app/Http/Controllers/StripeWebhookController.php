<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionPaymentFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        SubscriptionPaymentFulfillmentService $fulfillment,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        Log::info('Stripe webhook received', [
            'ip' => $request->ip(),
            'payload_bytes' => strlen($payload),
            'has_stripe_signature' => $signature !== '',
            'user_agent' => $request->userAgent(),
        ]);

        $secret = config('stripe.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            Log::error('Stripe webhook rejected: STRIPE_WEBHOOK_SECRET is not configured');

            abort(503);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $exception) {
            Log::warning('Stripe webhook signature verification failed', [
                'message' => $exception->getMessage(),
                'has_stripe_signature' => $signature !== '',
            ]);

            return response('Invalid payload', 400);
        } catch (UnexpectedValueException $exception) {
            Log::warning('Stripe webhook payload invalid', [
                'message' => $exception->getMessage(),
                'payload_bytes' => strlen($payload),
            ]);

            return response('Invalid payload', 400);
        }

        Log::info('Stripe webhook event parsed', [
            'event_id' => $event->id ?? null,
            'event_type' => $event->type ?? null,
            'livemode' => $event->livemode ?? null,
            'api_version' => $event->api_version ?? null,
        ]);

        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object ?? null;

            Log::info('Stripe webhook handling payment_intent.succeeded', [
                'event_id' => $event->id ?? null,
                'payment_intent_id' => $intent->id ?? null,
                'payment_intent_status' => $intent->status ?? null,
                'metadata' => self::loggableMetadata($intent),
            ]);

            $fulfilled = $intent !== null
                ? $fulfillment->fulfillFromWebhookPaymentIntent($intent)
                : false;

            Log::info('Stripe webhook payment_intent.succeeded processed', [
                'event_id' => $event->id ?? null,
                'payment_intent_id' => $intent->id ?? null,
                'fulfilled' => $fulfilled,
            ]);
        } else {
            Log::info('Stripe webhook event ignored (no handler)', [
                'event_id' => $event->id ?? null,
                'event_type' => $event->type ?? null,
            ]);
        }

        Log::info('Stripe webhook responded OK', [
            'event_id' => $event->id ?? null,
            'event_type' => $event->type ?? null,
        ]);

        return response('OK', 200);
    }

    /**
     * @return array<string, string>|null
     */
    private static function loggableMetadata(?object $intent): ?array
    {
        if ($intent === null) {
            return null;
        }

        $metadata = $intent->metadata ?? null;
        if ($metadata === null) {
            return null;
        }

        if (is_object($metadata) && method_exists($metadata, 'toArray')) {
            $metadata = $metadata->toArray();
        }

        if (! is_array($metadata)) {
            return null;
        }

        $result = [];
        foreach ($metadata as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $result[(string) $key] = (string) $value;
            }
        }

        return $result === [] ? null : $result;
    }
}
