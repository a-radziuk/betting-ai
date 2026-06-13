<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app_page_title('Crypto Payment') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('subscribe.payment', ['plan' => $plan['id']]) }}">← {{ __('Back to payment options') }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ __('Crypto payment') }}</h1>
        <p class="meta">
            {{ __('Plan') }}: {{ $plan['name'] }} · {{ $plan['price_label'] }}
        </p>
    </section>

    @if (session('status'))
        <div class="event-empty" style="margin-bottom: 12px;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="event-empty" style="margin-bottom: 12px;">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="card subscribe-payment-card">
        <h2 class="subscribe-plan-name">{{ $payment->wallet_label }}</h2>

        <dl class="subscribe-crypto-details">
            <div class="subscribe-crypto-detail-row">
                <dt>{{ __('Wallet address') }}</dt>
                <dd><code class="subscribe-crypto-code">{{ $payment->wallet_address }}</code></dd>
            </div>
            <div class="subscribe-crypto-detail-row">
                <dt>{{ __('Payment code (memo)') }}</dt>
                <dd><code class="subscribe-crypto-code subscribe-crypto-code--emphasis">{{ $payment->payment_code }}</code></dd>
            </div>
            <div class="subscribe-crypto-detail-row">
                <dt>{{ __('Amount') }}</dt>
                <dd>{{ $plan['price_label'] }}</dd>
            </div>
        </dl>

        <p class="subscribe-crypto-instructions">
            {{ __('Send the exact amount in USDT to the wallet address above. You must include the payment code in the transfer memo or note so we can match your payment.') }}
        </p>

        @if ($payment->isPendingApproval() || $payment->isPendingAdminReview())
            <p class="subscribe-crypto-status">{{ __('Status: awaiting admin approval.') }}</p>
        @elseif ($payment->isApproved())
            <p class="subscribe-crypto-status subscribe-crypto-status--ok">{{ __('Status: approved. Your subscription is active.') }}</p>
        @else
            <form method="POST" action="{{ route('subscribe.payment.crypto.paid', ['plan' => $plan['id'], 'wallet' => $payment->wallet_key]) }}" class="subscribe-crypto-paid-form">
                @csrf
                <button type="submit" class="btn btn-primary">{{ __('I have paid') }}</button>
            </form>
        @endif
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
