@if ($eventBets->isNotEmpty())
    <section class="event-tips-section" aria-labelledby="event-tips-title">
        <h2 id="event-tips-title" class="event-tips-title">Player tips</h2>
        <div class="event-tips-grid">
            @foreach ($eventBets as $bet)
                @php
                    $user = $bet->user;
                    $wallet = $user?->wallet;
                    $currency = $wallet?->currency ?? 'EUR';
                    $totalResult = $wallet ? (float) $wallet->total_result : 0.0;
                    $avatarUrl = $user?->profileAvatarUrl();
                    $nameTrim = trim((string) ($user?->name ?? ''));
                    $initial = mb_strtoupper(mb_substr($nameTrim !== '' ? $nameTrim : '?', 0, 1));
                    $market = $bet->odd?->selection?->market;
                    $selectionName = $bet->odd?->selection?->name;
                    $marketLabel = $market
                        ? trim($market->type.' · '.$market->period.(! is_null($market->line) ? ' · Line '.$market->line : ''))
                        : '—';
                    $betFormSegments = $user
                        ? \App\Support\UserBetFormIcons::fromBets($user->bets)
                        : [];
                @endphp
                <article class="event-tip-card">
                    <header class="event-tip-card-head">
                        <div class="event-tip-card-avatar">
                            @if ($avatarUrl)
                                <img
                                    src="{{ $avatarUrl }}"
                                    alt=""
                                    class="event-tip-card-avatar-img"
                                    loading="lazy"
                                    decoding="async"
                                />
                            @else
                                <span class="event-tip-card-avatar-placeholder" aria-hidden="true">{{ $initial }}</span>
                            @endif
                        </div>
                        <div class="event-tip-card-user">
                            @if ($user)
                                <a href="{{ route('players.show', $user) }}" class="event-tip-card-name">{{ $user->name }}</a>
                            @else
                                <span class="event-tip-card-name">—</span>
                            @endif
                            <p @class([
                                'event-tip-card-result',
                                'event-tip-card-result--pos' => $totalResult >= 0,
                                'event-tip-card-result--neg' => $totalResult < 0,
                            ])>
                                {{ __('Total result') }}:
                                {{ $totalResult >= 0 ? '+' : '' }}{{ number_format($totalResult, 2) }} {{ $currency }}
                            </p>
                            @if (count($betFormSegments) > 0)
                                <div class="welcome-bettor-card-form event-tip-card-form" role="group" aria-label="{{ __('Recent bet results') }}">
                                    @foreach ($betFormSegments as $seg)
                                        <span
                                            class="form-icon form-icon--{{ $seg['css'] }}"
                                            title="{{ e($seg['tooltip']) }}"
                                        >{{ $seg['letter'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </header>
                    <dl class="event-tip-card-pick">
                        <div class="event-tip-card-pick-row">
                            <dt>{{ __('Market') }}</dt>
                            <dd>{{ $marketLabel }}</dd>
                        </div>
                        <div class="event-tip-card-pick-row event-tip-card-pick-row--inline">
                            <div>
                                <dt>{{ __('Selection') }}</dt>
                                <dd>{{ $selectionName ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Odds') }}</dt>
                                <dd class="event-tip-card-odds">{{ number_format((float) $bet->odds_at_bet, 2) }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('Stake') }}</dt>
                                <dd class="event-tip-card-stake">{{ number_format((float) $bet->stake, 2) }} {{ $currency }}</dd>
                            </div>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>
    </section>
@endif
