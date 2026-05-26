@if ($events->isEmpty())
    <div class="empty">{{ __('No upcoming events found. Seed more data and refresh.') }}</div>
@else
    @php
        $tz = config('app.timezone');
        $todayKey = now()->timezone($tz)->format('Y-m-d');
        $eventsByDay = $events->groupBy(fn ($e) => $e->start_time->timezone($tz)->format('Y-m-d'));
    @endphp
    @foreach ($eventsByDay as $dayKey => $dayEvents)
        <div class="welcome-events-section">
            <h2 class="welcome-events-section-title">
                @if ($dayKey === $todayKey)
                    {{ __('Today') }}
                @else
                    {{ \Carbon\Carbon::createFromFormat('Y-m-d', $dayKey, $tz)->locale(app()->getLocale())->translatedFormat('l, j F Y') }}
                @endif
            </h2>
            <table class="welcome-events-table">
                <thead>
                    <tr>
                        <th class="welcome-time-col">{{ __('Time') }}</th>
                        <th class="welcome-tournament-col">{{ __('Tournament') }}</th>
                        <th class="welcome-match-col">{{ __('Match') }}</th>
                        <th class="welcome-tips-col">{{ __('Tips') }}</th>
                        <th class="welcome-1x2-col">{{ __('Home') }}</th>
                        <th class="welcome-1x2-col">{{ __('Draw') }}</th>
                        <th class="welcome-1x2-col">{{ __('Away') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dayEvents as $event)
                        @php
                            $matchResult = $event->markets->firstWhere('period', \App\Models\Market::PERIOD_FULL_TIME)
                                ?? $event->markets->first();
                            $bySelection = $matchResult ? $matchResult->selections->keyBy('name') : collect();
                            $oddStr = function (?string $name) use ($bySelection) {
                                $odd = $bySelection->get($name)?->odds->first();

                                return $odd !== null ? number_format((float) $odd->odds, 2) : '—';
                            };
                            $kickoffTime = $event->start_time->timezone($tz)->translatedFormat('H:i');
                            $tournamentName = $event->tournament?->localizedName() ?? '—';
                            $tipsCount = (int) ($event->user_bets_count ?? 0);
                            $tipsTooltip = $tipsCount === 1
                                ? __('1 player tip on this match')
                                : __(':count player tips on this match', ['count' => number_format($tipsCount)]);
                        @endphp
                        <tr data-clickable onclick="window.location='{{ route('events.show', $event) }}'">
                            <td class="welcome-time-col">
                                <time datetime="{{ $event->start_time->toIso8601String() }}">
                                    {{ $kickoffTime }}
                                </time>
                            </td>
                            <td class="welcome-tournament-col">{{ $tournamentName }}</td>
                            <td class="welcome-match-col">
                                <div class="welcome-match-teams">
                                    {{ $event->homeTeam?->resolvedDisplayName() ?? __('Team #:id', ['id' => $event->home_team_id]) }}
                                    {{ __('vs') }}
                                    {{ $event->awayTeam?->resolvedDisplayName() ?? __('Team #:id', ['id' => $event->away_team_id]) }}
                                </div>
                                <div class="welcome-match-meta">
                                    <time datetime="{{ $event->start_time->toIso8601String() }}" class="welcome-match-meta-time">{{ $kickoffTime }}</time>
                                    <span class="welcome-match-meta-sep" aria-hidden="true">·</span>
                                    <span class="welcome-match-meta-tournament">{{ $tournamentName }}</span>
                                    <span class="welcome-match-meta-sep" aria-hidden="true">·</span>
                                    <span class="welcome-match-meta-tips" title="{{ $tipsTooltip }}">
                                        <span class="welcome-tips-badge welcome-tips-badge--compact" aria-hidden="true">{{ number_format($tipsCount) }}</span>
                                        {{ $tipsCount === 1 ? __('tip') : __('tips') }}
                                    </span>
                                </div>
                            </td>
                            <td class="welcome-tips-col">
                                <span class="welcome-tips-badge" title="{{ $tipsTooltip }}">{{ number_format($tipsCount) }}</span>
                            </td>
                            <td class="welcome-odds welcome-1x2-col">{{ $oddStr(\App\Models\Selection::NAME_HOME) }}</td>
                            <td class="welcome-odds welcome-1x2-col">{{ $oddStr(\App\Models\Selection::NAME_DRAW) }}</td>
                            <td class="welcome-odds welcome-1x2-col">{{ $oddStr(\App\Models\Selection::NAME_AWAY) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endif
