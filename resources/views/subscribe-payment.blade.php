<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('BetAI | Payment') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('subscribe') }}">← {{ __('Back to plans') }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ __('Payment') }}</h1>
        <p class="meta">{{ __('Complete your subscription to unlock player tips.') }}</p>
    </section>

    <section class="card subscribe-payment-card">
        <h2 class="subscribe-plan-name">{{ $plan['name'] }}</h2>
        <p class="subscribe-plan-duration">{{ $plan['duration_label'] }}</p>
        <p class="subscribe-plan-price">{{ $plan['price_label'] }}</p>

        @if ($cryptoPaymentEnabled && count($cryptoWallets) > 0)
            <section class="subscribe-payment-methods" aria-label="{{ __('Crypto payment') }}">
                <h3 class="subscribe-payment-methods-title">{{ __('Pay with crypto') }}</h3>
                <ul class="subscribe-crypto-wallet-list">
                    @foreach ($cryptoWallets as $cryptoWallet)
                        <li>
                            <a
                                href="{{ route('subscribe.payment.crypto', ['plan' => $plan['id'], 'wallet' => $cryptoWallet['key']]) }}"
                                class="btn btn-secondary subscribe-crypto-wallet-link"
                            >
                                {{ $cryptoWallet['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @feature('subscription_stripe_payments')
            @if ($stripeReady)
                @if ($cryptoPaymentEnabled && count($cryptoWallets) > 0)
                    <h3 class="subscribe-payment-methods-title">{{ __('Pay with card') }}</h3>
                @endif
                <div
                    id="subscribe-stripe-payment"
                    class="subscribe-stripe-payment"
                    data-publishable-key="{{ $stripePublishableKey }}"
                    data-intent-url="{{ route('subscribe.payment.stripe-intent', ['plan' => $plan['id']]) }}"
                    data-return-url="{{ route('subscribe.payment.complete', ['plan' => $plan['id']]) }}"
                >
                    <div id="payment-element" class="subscribe-stripe-element"></div>
                    <p id="payment-message" class="subscribe-payment-message" role="alert" hidden></p>
                    <button type="button" id="submit-payment" class="btn btn-primary subscribe-stripe-submit">
                        {{ __('Pay with card') }}
                    </button>
                </div>
                @vite(['resources/js/subscribe-payment-stripe.js'])
            @else
                <p class="subscribe-payment-stub">
                    {{ __('Card payments are not configured. Set STRIPE_KEY and STRIPE_SECRET in your environment.') }}
                </p>
            @endif
        @else
            <p class="subscribe-payment-stub">{{ __('Payment integration is coming soon. No charge has been made.') }}</p>
        @endfeature
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
