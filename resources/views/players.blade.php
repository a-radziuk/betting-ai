<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('BetAI | Players') }}</title>
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
        <h1>{{ __('Players') }}</h1>
        <p class="meta">{{ __('All registered users and their wallet balances.') }}</p>
    </section>

    <section class="card overflow-hidden">
        @if ($players->isEmpty())
            <div class="empty">{{ __('No users yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table>
                    <thead>
                    <tr>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Recent bets') }}</th>
                        <th class="text-right">{{ __('Wallet balance') }}</th>
                        <th class="text-right">{{ __('In play') }}</th>
                        <th class="text-right">{{ __('Total result') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($players as $player)
                        <tr data-clickable onclick="window.location='{{ route('players.show', ['user' => $player->id]) }}'">
                            <td class="text-[#dce7ff]">{{ $player->name }}</td>
                            <td class="form-icons-cell align-middle">
                                @php
                                    $betFormSegments = \App\Support\UserBetFormIcons::fromBets($player->bets, true);
                                @endphp
                                @if (count($betFormSegments) > 0)
                                    @foreach ($betFormSegments as $seg)
                                        <span
                                            class="form-icon form-icon--{{ $seg['css'] }}"
                                            title="{{ e($seg['tooltip']) }}"
                                        >{{ $seg['letter'] }}</span>
                                    @endforeach
                                @else
                                    <span class="text-[#9fb0d3] text-sm">—</span>
                                @endif
                            </td>
                            <td class="text-right tabular-nums text-[#eaf0ff]">
                                {{ number_format((float) $player->wallet_balance, 2) }}
                                <span class="text-[#9fb0d3]">{{ $player->wallet_currency }}</span>
                            </td>
                            <td class="text-right tabular-nums text-[#eaf0ff]">
                                {{ number_format((float) $player->wallet_amount_in_play, 2) }}
                            </td>
                            <td class="text-right tabular-nums">
                                @php
                                    $tr = (float) $player->wallet_total_result;
                                    $color = $tr > 0.000001 ? '#4cff9d' : ($tr < -0.000001 ? '#ff9a9a' : '#9fb0d3');
                                @endphp
                                <span style="color: {{ $color }};">
                                    {{ $tr > 0 ? '+' : '' }}{{ number_format($tr, 2) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($players->hasPages())
                <div class="dashboard-pagination card-pad border-t border-[rgba(130,162,255,0.2)]">
                    {{ $players->links('vendor.pagination.tailwind') }}
                </div>
            @endif
        @endif
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>

