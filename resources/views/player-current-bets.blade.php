<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BetAI | Current bets</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('players.show', ['user' => $player->id]) }}">← Back to player</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ $player->name }}</h1>
        <p class="meta">Current (unresolved) bets, ordered by nearest event.</p>
    </section>

    <section class="card overflow-hidden">
        @if ($bets->isEmpty())
            <div class="empty">No current bets.</div>
        @else
            <div class="overflow-x-auto">
                <table>
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Bet</th>
                        <th class="text-right">Odd</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Potential</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($bets as $bet)
                        @php
                            $event = $bet->event;
                            $eventName = $event && $event->homeTeam && $event->awayTeam
                                ? $event->homeTeam->name . ' — ' . $event->awayTeam->name
                                : '—';
                            $eventTime = $event?->start_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—';

                            $selection = $bet->odd?->selection?->name ?? '—';
                            $market = $bet->odd?->selection?->market?->type;
                            $betLabel = $market ? "{$selection} ({$market})" : $selection;
                        @endphp
                        <tr>
                            <td>
                                <div class="text-[#dce7ff]">{{ $eventName }}</div>
                                <div class="text-xs text-[#9fb0d3] mt-1 tabular-nums">{{ $eventTime }}</div>
                            </td>
                            <td class="text-[#dce7ff]">{{ $betLabel }}</td>
                            <td class="text-right tabular-nums text-[#8bffcd]">{{ number_format((float) $bet->odds_at_bet, 2) }}</td>
                            <td class="text-right tabular-nums text-[#eaf0ff]">{{ number_format((float) $bet->stake, 2) }}</td>
                            <td class="text-right tabular-nums text-[#eaf0ff]">{{ number_format((float) $bet->potential_return, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($bets->hasPages())
                <div class="dashboard-pagination card-pad border-t border-[rgba(130,162,255,0.2)]">
                    {{ $bets->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        @endif
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>

