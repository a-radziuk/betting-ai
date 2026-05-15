<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BetAI | Player stats</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('players.index') }}">← Back to players</a>
    </div>
</div>

<main class="container">
    @php
        $avatarUrl = $player->profileAvatarUrl();
        $hasProfileDetails = $avatarUrl !== null
            || filled($player->tagline)
            || filled($player->bio)
            || filled($player->city)
            || filled($player->country);
    @endphp

    <section class="hero">
        <h1>{{ $player->name }}</h1>
        <p class="meta">Settled bets by event date, newest first.</p>
    </section>

    @if ($hasProfileDetails)
        <section class="card card-pad player-profile" style="margin-bottom: 12px;">
            <dl class="player-profile-dl">
                @if ($avatarUrl)
                    <div class="player-profile-row">
                        <dt class="player-profile-label">{{ __('Photo') }}</dt>
                        <dd class="player-profile-value">
                            <img
                                src="{{ $avatarUrl }}"
                                alt=""
                                class="player-profile-avatar"
                                loading="lazy"
                                decoding="async"
                            />
                        </dd>
                    </div>
                @endif
                @if (filled($player->tagline))
                    <div class="player-profile-row">
                        <dt class="player-profile-label">{{ __('Tagline') }}</dt>
                        <dd class="player-profile-value player-profile-tagline">{{ $player->tagline }}</dd>
                    </div>
                @endif
                @if (filled($player->bio))
                    <div class="player-profile-row">
                        <dt class="player-profile-label">{{ __('Bio') }}</dt>
                        <dd class="player-profile-value player-profile-bio">{{ $player->bio }}</dd>
                    </div>
                @endif
                @if (filled($player->city))
                    <div class="player-profile-row">
                        <dt class="player-profile-label">{{ __('City') }}</dt>
                        <dd class="player-profile-value">{{ $player->city }}</dd>
                    </div>
                @endif
                @if (filled($player->country))
                    <div class="player-profile-row">
                        <dt class="player-profile-label">{{ __('Country') }}</dt>
                        <dd class="player-profile-value">{{ $player->country }}</dd>
                    </div>
                @endif
            </dl>
        </section>
    @endif

    @php
        $resultValue = (float) ($player->wallet->total_result);
        $resultColor = $resultValue > 0.000001 ? '#4cff9d' : ($resultValue < -0.000001 ? '#ff9a9a' : '#9fb0d3');
    @endphp

    <div class="event-empty user-results" style="margin-bottom: 12px;">
        @include('players.partials.wallet-result-chart', ['resultChart' => $resultChart])
        <span class="user-results-item">
            <span class="user-results-label">Currently in play</span>
            <span class="user-results-value">{{ number_format((float) $player->wallet->amount_in_play, 2) }}</span>
            <a href="{{ route('players.current', ['user' => $player->id]) }}" class="subbar-back" style="margin-top: 6px;">
                See bets
            </a>
        </span>
        <span class="user-results-item">
            <span class="user-results-label">Result</span>
            <span class="user-results-value" style="color: {{ $resultColor }};">
                {{ $resultValue > 0 ? '+' : '' }}{{ number_format($resultValue, 2) }}
            </span>
        </span>
    </div>

    <section class="card overflow-hidden">
        @if ($bets->isEmpty())
            <div class="empty">No resolved bets yet.</div>
        @else
            <div class="overflow-x-auto">
                <table>
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Bet</th>
                        <th class="text-right">Odd</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Won / lost</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($bets as $bet)
                        @php
                            $event = $bet->event;
                            $eventName = $event && $event->homeTeam && $event->awayTeam
                                ? $event->homeTeam->resolvedDisplayName() . ' — ' . $event->awayTeam->resolvedDisplayName()
                                : '—';
                            $eventTime = $event?->start_time?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? '—';
                            $eventScore = filled($event?->score) ? $event->score : '—';

                            $selection = $bet->odd?->selection?->name ?? '—';
                            $market = $bet->odd?->selection?->market?->type;
                            $betLabel = $market ? "{$selection} ({$market})" : $selection;

                            $stake = (float) $bet->stake;
                            $potential = (float) $bet->potential_return;

                            $delta = 0.0;
                            if ($bet->status === \App\Models\UserBet::STATUS_WON) {
                                $delta = $potential - $stake;
                            } elseif ($bet->status === \App\Models\UserBet::STATUS_LOST) {
                                $delta = -$stake;
                            }

                            $deltaColor = $delta > 0.000001 ? '#4cff9d' : ($delta < -0.000001 ? '#ff9a9a' : '#9fb0d3');
                        @endphp
                        <tr>
                            <td>
                                <div class="text-[#dce7ff]">{{ $eventName }}</div>
                                <div class="text-xs text-[#9fb0d3] mt-1 tabular-nums">{{ $eventTime }} · {{ $eventScore }}</div>
                            </td>
                            <td class="text-[#dce7ff]">{{ $betLabel }}</td>
                            <td class="text-right tabular-nums text-[#8bffcd]">{{ number_format((float) $bet->odds_at_bet, 2) }}</td>
                            <td class="text-right tabular-nums text-[#eaf0ff]">{{ number_format($stake, 2) }}</td>
                            <td class="text-right tabular-nums">
                                <span style="color: {{ $deltaColor }};">
                                    {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2) }}
                                </span>
                            </td>
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

