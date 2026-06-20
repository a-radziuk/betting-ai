    @if (isset($topTournaments) && $topTournaments->isNotEmpty())
        <section class="card tournament-leagues-line" aria-label="{{ __('Featured tournaments') }}">
            <div class="tournament-leagues-line-inner">
                @foreach ($topTournaments as $t)
                    <a href="{{ route('tournaments.show', $t) }}" class="tournament-league-link">{{ $t->localizedName() }}</a>
                    @if (! $loop->last)
                        <span class="tournament-league-sep" aria-hidden="true">·</span>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    @php
        $topUserMetrics = \App\Support\HomepageTopUserMetrics::forHomepage();
        $showTopBettorsFromMetrics = $topUserMetrics->isNotEmpty();
        $showTopBettorsFromWallet = ! $showTopBettorsFromMetrics && isset($topBettors) && $topBettors->isNotEmpty();
    @endphp

    @if ($showTopBettorsFromMetrics || $showTopBettorsFromWallet)
        <section class="welcome-top-bettors" aria-labelledby="welcome-top-bettors-title">
            <h2 id="welcome-top-bettors-title" class="welcome-top-bettors-title">{{ __('Top bettors') }}</h2>
            <p class="welcome-top-bettors-lead">
                @if ($showTopBettorsFromMetrics)
                    {{ __('Top players, ranked by performance metrics.') }}
                @else
                    {{ __('Top players, ranked by lifetime wallet result.') }}
                @endif
            </p>
            @if ($showTopBettorsFromMetrics)
                <div class="welcome-top-bettors-grid">
                    @foreach ($topUserMetrics as $metric)
                        @php
                            $user = $metric->user;
                            $currency = $user->wallet?->currency ?? 'EUR';
                            $avatarUrl = $user->profileAvatarUrl();
                            $nameTrim = trim((string) $user->name);
                            $initial = mb_strtoupper(mb_substr($nameTrim !== '' ? $nameTrim : '?', 0, 1));
                        @endphp
                        <a
                            href="{{ route('players.show', $user) }}"
                            class="welcome-bettor-card welcome-bettor-card-link"
                            aria-label="{{ __('View :name player page', ['name' => $user->name]) }}"
                        >
                            <div class="welcome-bettor-card-avatar">
                                @if ($avatarUrl)
                                    <img
                                        src="{{ $avatarUrl }}"
                                        alt=""
                                        class="welcome-bettor-card-avatar-img"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                @else
                                    <span class="welcome-bettor-card-avatar-placeholder" aria-hidden="true">{{ $initial }}</span>
                                @endif
                            </div>
                            <div class="welcome-bettor-card-body">
                                <h3 class="welcome-bettor-card-name">{{ $user->name }}</h3>
                                <p class="welcome-bettor-card-result welcome-bettor-card-result--pos">
                                    +{{ number_format((float) $metric->amount, 2) }} {{ $currency }}
                                </p>
                                <p class="welcome-bettor-card-bets-meta">
                                    <span class="welcome-bettor-card-bets-count">{{ $metric->typeLabel() }}</span>
                                </p>
                                @if (! empty($metric->bets_stats))
                                    <div class="welcome-bettor-card-form" role="group" aria-label="{{ __('Bet results') }}">
                                        <span class="form-icon form-icon--w" title="{{ __('Won') }}">{{ number_format((int) ($metric->bets_stats['won'] ?? 0)) }}</span>
                                        <span class="form-icon form-icon--l" title="{{ __('Lost') }}">{{ number_format((int) ($metric->bets_stats['lost'] ?? 0)) }}</span>
                                        <span class="form-icon form-icon--d" title="{{ __('Drawn') }}">{{ number_format((int) ($metric->bets_stats['drawn'] ?? 0)) }}</span>
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="welcome-top-bettors-grid">
                    @foreach ($topBettors as $user)
                        @php
                            $wallet = $user->wallet;
                            $currency = $wallet?->currency ?? 'EUR';
                            $betCount = (int) ($user->bets_count ?? 0);
                            $stakeSum = (float) ($user->bets_sum_stake ?? 0);
                            $total = $wallet ? (float) $wallet->total_result : 0.0;
                            $avatarUrl = $user->profileAvatarUrl();
                            $nameTrim = trim((string) $user->name);
                            $initial = mb_strtoupper(mb_substr($nameTrim !== '' ? $nameTrim : '?', 0, 1));
                            $betFormSegments = \App\Support\UserBetFormIcons::fromBets($user->bets);
                        @endphp
                        <a
                            href="{{ route('players.show', $user) }}"
                            class="welcome-bettor-card welcome-bettor-card-link"
                            aria-label="{{ __('View :name player page', ['name' => $user->name]) }}"
                        >
                            <div class="welcome-bettor-card-avatar">
                                @if ($avatarUrl)
                                    <img
                                        src="{{ $avatarUrl }}"
                                        alt=""
                                        class="welcome-bettor-card-avatar-img"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                @else
                                    <span class="welcome-bettor-card-avatar-placeholder" aria-hidden="true">{{ $initial }}</span>
                                @endif
                            </div>
                            <div class="welcome-bettor-card-body">
                                <h3 class="welcome-bettor-card-name">{{ $user->name }}</h3>
                                <p class="welcome-bettor-card-bets-meta">
                                    <span class="welcome-bettor-card-bets-count">{{ $betCount }} {{ $betCount === 1 ? __('bet') : __('bets') }}</span>
                                    <span class="welcome-bettor-card-bets-sep" aria-hidden="true">·</span>
                                    <span class="welcome-bettor-card-bets-stake">{{ number_format($stakeSum, 2) }} {{ $currency }}</span>
                                </p>
                                <p @class([
                                    'welcome-bettor-card-result',
                                    'welcome-bettor-card-result--pos' => $total >= 0,
                                    'welcome-bettor-card-result--neg' => $total < 0,
                                ])>
                                    {{ $total >= 0 ? '+' : '' }}{{ number_format($total, 2) }} {{ $currency }}
                                </p>
                                @if (count($betFormSegments) > 0)
                                    <div class="welcome-bettor-card-form" role="group" aria-label="{{ __('Recent bet results') }}">
                                        @foreach ($betFormSegments as $seg)
                                            <span
                                                class="form-icon form-icon--{{ $seg['css'] }}"
                                                title="{{ e($seg['tooltip']) }}"
                                            >{{ $seg['letter'] }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <p class="welcome-see-all-players">
        <a href="{{ route('players.index') }}" class="tournament-see-all-link">{{ __('See all players') }}</a>
    </p>

    @include('partials.welcome-featured-bets', ['featuredBets' => $featuredBets ?? collect()])

    <section id="upcoming-events" class="card">
        @include('partials.upcoming-events-table', ['events' => $events])
    </section>
