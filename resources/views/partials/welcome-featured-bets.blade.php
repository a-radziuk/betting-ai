@if ($featuredBets->isNotEmpty())
    <section class="welcome-top-bettors welcome-featured-bets" aria-labelledby="welcome-featured-bets-title">
        <h2 id="welcome-featured-bets-title" class="welcome-top-bettors-title">{{ __('Latest bet results') }}</h2>
        <div class="welcome-top-bettors-grid">
            @foreach ($featuredBets as $bet)
                @php
                    $user = $bet->user;
                    $event = $bet->event;
                    $wallet = $user?->wallet;
                    $currency = $wallet?->currency ?? 'EUR';
                    $result = (float) ($bet->real_return ?? 0);
                    $avatarUrl = $user?->profileAvatarUrl();
                    $nameTrim = trim((string) ($user?->name ?? ''));
                    $initial = mb_strtoupper(mb_substr($nameTrim !== '' ? $nameTrim : '?', 0, 1));
                    $homeName = $event?->homeTeam?->resolvedDisplayName() ?? __('Team #:id', ['id' => $event?->home_team_id ?? '—']);
                    $awayName = $event?->awayTeam?->resolvedDisplayName() ?? __('Team #:id', ['id' => $event?->away_team_id ?? '—']);
                    $matchup = $homeName.' '.__('vs').' '.$awayName;
                    $cardModifier = match ($bet->status) {
                        \App\Models\UserBet::STATUS_WON => 'welcome-bettor-card--won',
                        \App\Models\UserBet::STATUS_LOST => 'welcome-bettor-card--lost',
                        \App\Models\UserBet::STATUS_VOID => 'welcome-bettor-card--void',
                        default => null,
                    };
                @endphp
                <article @class(['welcome-bettor-card', $cardModifier])>
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
                        @if ($user)
                            <h3 class="welcome-bettor-card-name">
                                <a href="{{ route('players.show', $user) }}" class="welcome-featured-bet-player-link">{{ $user->name }}</a>
                            </h3>
                        @else
                            <h3 class="welcome-bettor-card-name">—</h3>
                        @endif
                        <p class="welcome-bettor-card-bets-meta">
                            @if ($event)
                                <a href="{{ route('events.show', $event) }}" class="welcome-featured-bet-event-link">{{ $matchup }}</a>
                            @else
                                <span>{{ $matchup }}</span>
                            @endif
                        </p>
                        <p class="welcome-bettor-card-bets-meta">
                            <span class="welcome-bettor-card-bets-count">{{ $bet->statusLabel() }}</span>
                            <span class="welcome-bettor-card-bets-sep" aria-hidden="true">·</span>
                            <span class="welcome-bettor-card-bets-stake">{{ number_format((float) $bet->stake, 2) }} {{ $currency }} @ {{ number_format((float) $bet->odds_at_bet, 2) }}</span>
                        </p>
                        <p @class([
                            'welcome-bettor-card-result',
                            'welcome-bettor-card-result--pos' => $result > 0,
                            'welcome-bettor-card-result--neg' => $result < 0,
                        ])>
                            {{ $result > 0 ? '+' : '' }}{{ number_format($result, 2) }} {{ $currency }}
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
