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
        $wallet = $player->wallet;
        $walletCurrency = $wallet?->currency ?? 'EUR';
        $walletStartBalance = $wallet !== null ? (float) $wallet->start_balance : null;
        $walletTotalResult = $wallet !== null ? (float) $wallet->total_result : null;
        $walletAmountInPlay = $wallet !== null ? (float) $wallet->amount_in_play : null;
        $absoluteBankValue = $wallet !== null ? (float) $wallet->balance : null;
        $hasProfileDetails = $absoluteBankValue !== null
            || $avatarUrl !== null
            || filled($player->tagline)
            || filled($player->bio)
            || filled($player->city)
            || filled($player->country);
    @endphp

    <section class="hero">
        <h1>{{ $player->name }}</h1>
        <p class="meta">Settled bets by settlement order, newest first.</p>
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
                @if ($absoluteBankValue !== null)
                    <div class="player-profile-row">
                        <dt class="player-profile-label">{{ __('Absolute Bank Value') }}</dt>
                        <dd class="player-profile-value player-profile-bank tabular-nums">
                            @include('players.partials.bank-formula', [
                                'walletStartBalance' => $walletStartBalance,
                                'walletTotalResult' => $walletTotalResult,
                                'walletAmountInPlay' => $walletAmountInPlay,
                                'absoluteBankValue' => $absoluteBankValue,
                                'walletCurrency' => $walletCurrency,
                            ])
                        </dd>
                    </div>
                @endif
            </dl>
        </section>
    @endif

    @php
        $resultColor = $totalResult > 0.000001 ? '#4cff9d' : ($totalResult < -0.000001 ? '#ff9a9a' : '#9fb0d3');
        $efficiencyColor = $efficiencyPercent === null
            ? '#9fb0d3'
            : ($efficiencyPercent > 0.000001 ? '#4cff9d' : ($efficiencyPercent < -0.000001 ? '#ff9a9a' : '#9fb0d3'));
    @endphp

    <div class="event-empty user-results" style="margin-bottom: 12px;">
        @include('players.partials.wallet-result-chart', ['resultChart' => $resultChart])
        <span class="user-results-item">
            <span class="user-results-label">Currently in play</span>
            <span class="user-results-value">{{ number_format((float) $player->wallet->amount_in_play, 2) }}</span>
            <span class="user-results-in-play-meta">
                {{ number_format($pendingBetCount) }} {{ $pendingBetCount === 1 ? __('bet') : __('bets') }}
            </span>
            <a href="{{ route('players.current', ['user' => $player->id]) }}" class="subbar-back" style="margin-top: 6px;">
                See bets
            </a>
        </span>
        <span class="user-results-item user-results-item--metrics">
            <div class="player-result-head">
                <span class="user-results-label">Result</span>
                <div class="player-result-outcomes" role="group" aria-label="{{ __('Settled bet outcomes') }}">
                    <span class="form-icon form-icon--w" title="{{ __('Won') }}">{{ number_format($wonBetCount) }}</span>
                    <span class="form-icon form-icon--l" title="{{ __('Lost') }}">{{ number_format($lostBetCount) }}</span>
                    <span class="form-icon form-icon--d" title="{{ __('Void') }}">{{ number_format($voidBetCount) }}</span>
                </div>
            </div>
            <div class="user-results-metric user-results-metric--duo">
                <div class="user-results-metric-duo-item">
                    @include('players.partials.metric-label', [
                        'label' => __('Bets'),
                        'hint' => __('Number of settled bets (won, lost, void, or cancelled).'),
                    ])
                    <span class="user-results-metric-value tabular-nums">{{ number_format($resolvedBetCount) }}</span>
                    @include('players.partials.metric-label', [
                        'label' => __('Turnover'),
                        'hint' => __('Total stake staked on all settled bets.'),
                    ])
                    <span class="user-results-metric-value tabular-nums">{{ number_format($turnover, 2) }}</span>
                </div>
            </div>
            <div class="user-results-metric">
                @include('players.partials.metric-label', [
                    'label' => __('Average stake'),
                    'hint' => __('Turnover divided by the number of settled bets.'),
                ])
                <span class="user-results-metric-value tabular-nums">
                    @if ($averageStake === null)
                        —
                    @else
                        {{ number_format($averageStake, 2) }}
                    @endif
                </span>
            </div>
            <div class="user-results-metric">
                @include('players.partials.metric-label', [
                    'label' => __('Won/Lost'),
                    'hint' => __('Net profit or loss on the wallet from all settled bets.'),
                ])
                <span class="user-results-metric-value tabular-nums" style="color: {{ $resultColor }};">
                    {{ $totalResult > 0 ? '+' : '' }}{{ number_format($totalResult, 2) }}
                </span>
            </div>
            <div class="user-results-metric">
                @include('players.partials.metric-label', [
                    'label' => __('Relative Efficiency'),
                    'hint' => __('Won/Lost divided by turnover, expressed as a percentage.'),
                ])
                <span class="user-results-metric-value tabular-nums" style="color: {{ $efficiencyColor }};">
                    @if ($efficiencyPercent === null)
                        —
                    @else
                        {{ $efficiencyPercent > 0 ? '+' : '' }}{{ number_format($efficiencyPercent, 1) }}%
                    @endif
                </span>
            </div>
            <div class="user-results-metric">
                @include('players.partials.metric-label', [
                    'label' => __('Absolute Efficiency'),
                    'hint' => __('Won/Lost divided by starting balance, expressed as a percentage.'),
                ])
                <span class="user-results-metric-value tabular-nums" style="color: {{ $efficiencyColor }};">
                    @if ($efficiencyPercentAbsolute === null)
                        —
                    @else
                        {{ $efficiencyPercentAbsolute > 0 ? '+' : '' }}{{ $efficiencyPercentAbsolute }}%
                    @endif
                </span>
            </div>
        </span>
    </div>

    <section class="card overflow-hidden">
        @if ($bets->isNotEmpty())
            <div class="card-pad player-stats-download-bar">
                <a href="{{ route('players.bets.csv', ['user' => $player->id]) }}" class="subbar-back">
                    {{ __('Download CSV') }}
                </a>
            </div>
        @endif
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

