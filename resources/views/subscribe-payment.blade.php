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
        <p class="subscribe-payment-stub">{{ __('Payment integration is coming soon. No charge has been made.') }}</p>
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
