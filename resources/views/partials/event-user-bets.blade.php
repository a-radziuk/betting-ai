@if ($eventBets->isNotEmpty())
    @php
        $canSeeTips = ($event?->status === \App\Models\Event::STATUS_FINISHED)
            || (auth()->check() && auth()->user()->hasPrivelege(\App\Models\User::PRIVELEGE_SEE_TIPS));
        $isFinishedEvent = $event?->status === \App\Models\Event::STATUS_FINISHED;
    @endphp
    <section class="event-tips-section" aria-labelledby="event-tips-title">
        <h2 id="event-tips-title" class="event-tips-title">{{ __('Player tips') }}</h2>
        <div class="event-tips-grid">
            @foreach ($eventBets as $bet)
                @php
                    $user = $bet->user;
                    $wallet = $user?->wallet;
                    $currency = $wallet?->currency ?? 'EUR';
                    $totalResult = $wallet ? (float) $wallet->total_result : 0.0;
                    $displayResult = $isFinishedEvent
                        ? (float) ($bet->real_return ?? 0)
                        : $totalResult;
                    $startBalance = $wallet ? (float) $wallet->start_balance : 0.0;
                    $balance = $wallet ? (float) $wallet->balance : 0.0;
                    $efficiency = $startBalance > 0
                        ? (($balance - $startBalance) / $startBalance) * 100
                        : null;
                    $avatarUrl = $user?->profileAvatarUrl();
                    $nameTrim = trim((string) ($user?->name ?? ''));
                    $initial = mb_strtoupper(mb_substr($nameTrim !== '' ? $nameTrim : '?', 0, 1));
                    $market = $bet->odd?->selection?->market;
                    $selectionName = $bet->odd?->selection?->displayName($event);
                    $marketLabel = $market
                        ? trim($market->typeLabel().' · '.$market->period.(! is_null($market->line) ? ' · '.__('Line').' '.$market->line : ''))
                        : '—';
                    $betFormSegments = $user
                        ? \App\Support\UserBetFormIcons::fromBets($user->bets)
                        : [];
                    $tipCardClass = match ($bet->status) {
                        \App\Models\UserBet::STATUS_WON => 'event-tip-card--won',
                        \App\Models\UserBet::STATUS_VOID => 'event-tip-card--void',
                        \App\Models\UserBet::STATUS_LOST => 'event-tip-card--lost',
                        default => null,
                    };
                @endphp
                <article @class(['event-tip-card', $tipCardClass])>
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
                            @if ($isFinishedEvent)
                                <div @class([
                                    'event-tip-card-efficiency',
                                    'event-tip-card-efficiency--pos' => ($efficiency ?? 0) > 0,
                                    'event-tip-card-efficiency--neg' => ($efficiency ?? 0) < 0,
                                ])>
                                    @if ($efficiency !== null)
                                        {{ $efficiency > 0 ? '+' : '' }}{{ number_format($efficiency, 2) }}%
                                    @else
                                        —
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="event-tip-card-user">
                            <div class="event-tip-card-name-row">
                                @if ($user)
                                    <a href="{{ route('players.show', $user) }}" class="event-tip-card-name">{{ $user->name }}</a>
                                @else
                                    <span class="event-tip-card-name">—</span>
                                @endif
                                <span @class([
                                    'event-tip-card-result',
                                    'event-tip-card-result--pos' => $displayResult > 0,
                                    'event-tip-card-result--neg' => $displayResult < 0,
                                ])>
                                    {{ $displayResult > 0 ? '+' : '' }}{{ number_format($displayResult, 2) }}
                                </span>
                            </div>
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
                    @if ($canSeeTips)
                        <div class="event-tip-card-pick-box">
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
                        </div>
                    @elseif ($user)
                        <p class="event-tip-card-subscribe">
                            <a href="{{ route('subscribe') }}" class="event-tip-card-subscribe-link">
                                {{ __('Subscribe to see the tips') }}
                            </a>
                        </p>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif
