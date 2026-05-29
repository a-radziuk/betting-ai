<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('BetAI | Terms and Conditions') }}</title>
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
        <h1>{{ __('Terms and Conditions') }}</h1>
        <p class="meta">
            {{ __('Plan') }}: {{ $plan['name'] }} · {{ $plan['price_label'] }} · {{ $plan['duration_label'] }}
        </p>
    </section>

    @if ($errors->any())
        <div class="event-empty" style="margin-bottom: 12px;">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="card subscribe-terms-card">
        <div class="subscribe-terms-body">
            @include('legal.subscription-terms-content')
        </div>

        <form method="POST" action="{{ route('subscribe.terms.accept', ['plan' => $plan['id']]) }}" class="subscribe-terms-form">
            @csrf
            <label class="subscribe-terms-checkbox">
                <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms')) required>
                <span>{{ __('I have read and agree to the Terms and Conditions') }}</span>
            </label>
            <div class="subscribe-terms-actions">
                <a href="{{ route('subscribe') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('Continue to payment') }}</button>
            </div>
        </form>
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
