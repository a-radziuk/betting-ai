<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app_page_title('Subscribe') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ url('/') }}">← {{ __('Back to events') }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ __('Subscribe') }}</h1>
        <p class="meta">{{ app_brand('Choose a plan to unlock player tips and current bets across :app.') }}</p>
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

    @if ($plans === [])
        <div class="event-empty">{{ __('No subscription plans are available right now.') }}</div>
    @else
        <section class="subscribe-plans-grid" aria-label="{{ __('Subscription plans') }}">
            @foreach ($plans as $plan)
                <article class="subscribe-plan-card">
                    <h2 class="subscribe-plan-name">{{ $plan['name'] }}</h2>
                    <p class="subscribe-plan-duration">{{ $plan['duration_label'] }}</p>
                    <p class="subscribe-plan-price">{{ $plan['price_label'] }}</p>
                    <ul class="subscribe-plan-features">
                        <li>{{ __('View player betting tips on events') }}</li>
                        <li>{{ __('Download full player bet history as CSV') }}</li>
                        <li>{{ __('View full cumulative result trend charts') }}</li>
                    </ul>
                    <div class="subscribe-plan-action">
                        @if ($hasActiveSeeTips)
                            <button type="button" class="btn btn-secondary" disabled>
                                {{ __('Active') }}
                            </button>
                        @else
                            <a
                                href="{{ route('subscribe.terms', ['plan' => $plan['id']]) }}"
                                class="btn btn-primary"
                            >
                                {{ auth()->check() ? __('Subscribe') : __('Sign in to subscribe') }}
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>
    @endif
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
