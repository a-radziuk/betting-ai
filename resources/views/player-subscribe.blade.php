<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('BetAI | Subscribe') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('players.show', ['user' => $player->id]) }}">← {{ __('Back to player') }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ __('Subscribe') }}</h1>
        <p class="meta">{{ $player->name }}</p>
    </section>

    @if (session('status'))
        <div class="event-empty" style="margin-bottom: 12px;">
            {{ session('status') }}
        </div>
    @endif

    <section class="market-grid" style="grid-template-columns: 1fr;">
        <article class="market">
            <div class="market-head">
                <span>{{ __('Subscription') }}</span>
                <span class="period">{{ __('Follow this player') }}</span>
            </div>
            <div class="rows">
                @if (! $subscription)
                    <div class="row" style="justify-content: flex-end;">
                        <form method="POST" action="{{ route('players.subscribe.store', ['user' => $player->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">{{ __('Subscribe') }}</button>
                        </form>
                    </div>
                @else
                    <div class="row" style="justify-content: space-between;">
                        <span class="name">
                            {{ __('Subscribed since') }}
                            <strong class="tabular-nums">{{ $subscription->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</strong>
                        </span>
                        <button type="button" class="btn btn-secondary" disabled title="{{ __('Not implemented yet') }}">
                            {{ __('Unsubscribe') }}
                        </button>
                    </div>
                @endif
            </div>
        </article>
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>

