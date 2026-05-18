<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BetAI | Subscribe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ url('/') }}">← Back to events</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>Subscribe</h1>
        <p class="meta">Choose a plan to unlock player tips and current bets across BetAI.</p>
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

    @if ($hasActiveSeeTips)
        <div class="event-empty" style="margin-bottom: 12px;">
            {{ __('You already have access to tips.') }}
            @if ($seeTipsExpiresAt)
                {{ __('Access until :date.', ['date' => $seeTipsExpiresAt->timezone(config('app.timezone'))->format('Y-m-d')]) }}
            @endif
        </div>
    @endif

    <section class="subscribe-plans-grid" aria-label="Subscription plans">
        @foreach ($plans as $plan)
            @php
                $isFeatured = $plan['id'] === \App\Support\SubscriptionPlans::FREE_TRIAL;
            @endphp
            <article @class([
                'subscribe-plan-card',
                'subscribe-plan-card--featured' => $isFeatured,
                'subscribe-plan-card--disabled' => ! $plan['enabled'],
            ])>
                @if ($isFeatured)
                    <span class="subscribe-plan-badge">{{ __('Available now') }}</span>
                @endif
                <h2 class="subscribe-plan-name">{{ $plan['name'] }}</h2>
                <p class="subscribe-plan-duration">{{ $plan['duration_label'] }}</p>
                <ul class="subscribe-plan-features">
                    <li>{{ __('See player tips on events') }}</li>
                    <li>{{ __('View current bets from players') }}</li>
                </ul>
                <div class="subscribe-plan-action">
                    @if (! $plan['enabled'])
                        <button type="button" class="btn btn-secondary" disabled>
                            {{ __('Coming soon') }}
                        </button>
                    @elseif ($hasActiveSeeTips)
                        <button type="button" class="btn btn-secondary" disabled>
                            {{ __('Active') }}
                        </button>
                    @elseif ($plan['id'] === \App\Support\SubscriptionPlans::FREE_TRIAL)
                        @auth
                            <form method="POST" action="{{ route('subscribe.store') }}">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan['id'] }}">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Start free trial') }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">
                                {{ __('Sign in to start') }}
                            </a>
                        @endauth
                    @endif
                </div>
            </article>
        @endforeach
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
