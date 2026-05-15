@php
    $totalResultClass = $walletTotalResult > 0.000001
        ? 'player-profile-bank-term--result-pos'
        : ($walletTotalResult < -0.000001 ? 'player-profile-bank-term--result-neg' : '');
    $balanceClass = $absoluteBankValue < $walletStartBalance - 0.000001
        ? 'player-profile-bank-term--balance-neg'
        : 'player-profile-bank-term--balance-pos';
@endphp

<div class="player-profile-bank-formula" role="group" aria-label="{{ __('Absolute bank value breakdown') }}">
    <span class="player-profile-bank-term">
        <span class="player-profile-bank-amount">{{ number_format($walletStartBalance, 2) }}</span>
        @include('players.partials.metric-info', [
            'hint' => __('Starting wallet balance when the player began or was last reset.'),
        ])
    </span>
    <span class="player-profile-bank-op" aria-hidden="true">+</span>
    <span @class(['player-profile-bank-term', $totalResultClass])>
        <span class="player-profile-bank-amount">{{ number_format($walletTotalResult, 2) }}</span>
        @include('players.partials.metric-info', [
            'hint' => __('Net profit or loss from all settled bets (won, lost, void, or cancelled).'),
        ])
    </span>
    <span class="player-profile-bank-op" aria-hidden="true">−</span>
    <span class="player-profile-bank-term">
        <span class="player-profile-bank-amount">{{ number_format($walletAmountInPlay, 2) }}</span>
        @include('players.partials.metric-info', [
            'hint' => __('Stake currently locked in pending bets.'),
        ])
    </span>
    <span class="player-profile-bank-op player-profile-bank-equals" aria-hidden="true">=</span>
    <span @class(['player-profile-bank-term', $balanceClass])>
        <span class="player-profile-bank-amount">{{ number_format($absoluteBankValue, 2) }} {{ $walletCurrency }}</span>
        @include('players.partials.metric-info', [
            'hint' => __('Available balance: starting balance plus settled result minus stake in play.'),
        ])
    </span>
</div>
