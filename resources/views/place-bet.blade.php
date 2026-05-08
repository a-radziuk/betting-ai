<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BetAI | Place Bet</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('events.show', ['event' => $event->id]) }}">← Back to event</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>
            {{ $event->homeTeam?->name ?? ('Team #' . $event->home_team_id) }}
            vs
            {{ $event->awayTeam?->name ?? ('Team #' . $event->away_team_id) }}
        </h1>
        <p class="meta">
            Place your bet
        </p>
    </section>

    <section class="market-grid" style="grid-template-columns: 1fr;">
        <article class="market">
            <div class="market-head">
                <span>{{ $odd->selection?->market?->type ?? 'Market' }}</span>
                <span class="period">
                    {{ $odd->selection?->market?->period ?? '' }}
                    @if (! is_null($odd->selection?->market?->line))
                        | Line: {{ $odd->selection?->market?->line }}
                    @endif
                </span>
            </div>

            <div class="rows">
                <div class="row">
                    <span class="name">{{ $odd->selection?->name ?? 'Selection' }}</span>
                    <span class="odds">{{ number_format((float) $odd->odds, 2) }}</span>
                </div>
            </div>
        </article>
    </section>

    <div class="event-empty" style="margin-top: 12px;">
        Wallet balance: <strong>{{ number_format((float) $wallet->balance, 2) }} {{ $wallet->currency }}</strong>
    </div>

    <section style="margin-top: 16px;">
        <form method="POST" action="{{ route('bets.place.store', ['odd' => $odd->id]) }}" class="market" style="padding: 14px;">
            @csrf

            <div class="rows">
                <div class="row" style="grid-template-columns: 1fr;">
                    <label class="name" for="sum">Amount</label>
                    <input
                        id="sum"
                        name="sum"
                        type="number"
                        min="0.01"
                        step="0.01"
                        value="{{ old('sum') }}"
                        class="input-dark"
                        required
                    />
                    @error('sum')
                        <div style="margin-top: 8px; color: #b91c1c;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="bet-kpi" aria-live="polite">
                <div>
                    <div class="label">Potential gain</div>
                    <div class="value" id="potentialGain">0.00 {{ $wallet->currency }}</div>
                </div>
                <div>
                    <div class="label">Odds</div>
                    <div class="value">{{ number_format((float) $odd->odds, 2) }}</div>
                </div>
            </div>

            <div style="margin-top: 14px; display: flex; gap: 10px; align-items: center;">
                <button type="submit" class="btn btn-primary">Place bet</button>
                <a class="btn btn-secondary" href="{{ route('events.show', ['event' => $event->id]) }}">Cancel</a>
            </div>
        </form>
    </section>
</main>

@include('layouts.partials.betai-footer')

<script>
    (function () {
        const odds = Number({{ json_encode((float) $odd->odds) }});
        const input = document.getElementById('sum');
        const out = document.getElementById('potentialGain');
        const currency = "{{ $wallet->currency }}";

        function fmt(n) {
            if (!Number.isFinite(n)) return `0.00 ${currency}`;
            return `${n.toFixed(2)} ${currency}`;
        }

        function update() {
            const stake = Number(input.value);
            const gain = stake * odds;
            out.textContent = fmt(gain);
        }

        input.addEventListener('input', update);
        update();
    })();
</script>
</body>
</html>

