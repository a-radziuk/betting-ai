<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app_page_title('Payment') }}</title>
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

        @if ($metamaskPayment !== null)
            <section class="subscribe-payment-methods" aria-label="{{ __('MetaMask payment') }}">
                <h3 class="subscribe-payment-methods-title">{{ __('Pay with MetaMask') }}</h3>
                <p class="subscribe-payment-metamask-meta">
                    {{ __('Send :amount in USDT or USDC to our wallet using MetaMask.', ['amount' => $metamaskPayment['price_label']]) }}
                </p>
                <p class="subscribe-payment-metamask-meta">
                    {{ __('Destination wallet') }}:
                    <code class="subscribe-crypto-code">{{ $metamaskPayment['recipient'] }}</code>
                </p>
                <p class="subscribe-payment-metamask-hint">
                    {{ __('MetaMask may show the token contract address and 0 ETH — that is normal. Expand the transaction details to confirm the USDT/USDC transfer goes to the destination wallet above.') }}
                </p>
                <div
                    id="subscribe-metamask-payment"
                    class="subscribe-metamask-payment"
                    data-recipient="{{ $metamaskPayment['recipient'] }}"
                    @if ($metamaskPayment['usdt_contract'] !== null)
                        data-usdt-contract="{{ $metamaskPayment['usdt_contract'] }}"
                    @endif
                    @if ($metamaskPayment['usdc_contract'] !== null)
                        data-usdc-contract="{{ $metamaskPayment['usdc_contract'] }}"
                    @endif
                    data-chain-id="{{ $metamaskPayment['chain_id'] }}"
                    data-stablecoin-amount="{{ $metamaskPayment['stablecoin_amount'] }}"
                    @if ($metamaskPayment['eth_amount_wei'] !== null)
                        data-eth-amount-wei="{{ $metamaskPayment['eth_amount_wei'] }}"
                    @endif
                    data-record-url="{{ $metamaskPayment['record_url'] }}"
                >
                    <div class="subscribe-metamask-actions">
                        @if ($metamaskPayment['usdt_contract'] !== null)
                            <button type="button" id="metamask-pay-usdt" class="btn btn-secondary">
                                {{ __('Pay USDT with MetaMask') }}
                            </button>
                        @endif
                        @if ($metamaskPayment['usdc_contract'] !== null)
                            <button type="button" id="metamask-pay-usdc" class="btn btn-secondary">
                                {{ __('Pay USDC with MetaMask') }}
                            </button>
                        @endif
                        @if ($metamaskPayment['eth_amount_wei'] !== null)
                            <button type="button" id="metamask-pay-eth" class="btn btn-secondary">
                                {{ __('Pay ETH with MetaMask') }}
                            </button>
                        @endif
                    </div>
                    <p id="metamask-payment-message" class="subscribe-payment-message" role="alert" hidden></p>
                </div>
                @vite(['resources/js/subscribe-payment-metamask.js'])
            </section>
        @endif

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
