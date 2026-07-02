@if ($eventResults->isEmpty())
    <div class="empty">{{ $emptyMessage ?? __('No results recorded yet.') }}</div>
@else
    @php
        $tz = config('app.timezone');
        $todayKey = now()->timezone($tz)->format('Y-m-d');
        $grouped = $eventResults->groupBy(fn ($r) => $r->date->copy()->timezone($tz)->format('Y-m-d'));
    @endphp
    @foreach ($grouped as $dayKey => $rows)
        <div class="welcome-events-section">
            <h2 class="welcome-events-section-title">
                @if ($dayKey === $todayKey)
                    {{ __('Today') }}
                @else
                    {{ \Carbon\Carbon::createFromFormat('Y-m-d', $dayKey, $tz)->locale(app()->getLocale())->translatedFormat('l, j F Y') }}
                @endif
            </h2>
            <table class="welcome-events-table tournament-results-table">
                <thead>
                    <tr>
                        <th class="welcome-match-col">{{ __('Match') }}</th>
                        <th class="welcome-result-score-col">{{ __('Score') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $result)
                        <tr
                            @if ($result->event_id)
                                data-clickable
                                onclick="window.location='{{ route('events.show', $result->event_id) }}'"
                            @endif
                        >
                            <td class="welcome-match-col">
                                <div class="welcome-match-teams">
                                    {{ $result->homeTeam?->resolvedDisplayName() ?? __('Team #:id', ['id' => $result->home_team_id]) }}
                                    {{ __('vs') }}
                                    {{ $result->awayTeam?->resolvedDisplayName() ?? __('Team #:id', ['id' => $result->away_team_id]) }}
                                </div>
                            </td>
                            <td class="welcome-result-score-col welcome-odds">{{ \App\Support\EventScoreDisplay::forEventResult($result) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endif
