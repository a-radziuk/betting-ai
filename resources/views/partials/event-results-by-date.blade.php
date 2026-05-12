@if ($eventResults->isEmpty())
    <div class="empty">{{ $emptyMessage ?? 'No results recorded yet.' }}</div>
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
                    Today
                @else
                    {{ \Carbon\Carbon::createFromFormat('Y-m-d', $dayKey, $tz)->locale(app()->getLocale())->translatedFormat('l, j F Y') }}
                @endif
            </h2>
            <table class="welcome-events-table tournament-results-table">
                <thead>
                    <tr>
                        <th class="welcome-match-col">Match</th>
                        <th class="welcome-result-score-col">Score</th>
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
                                    {{ $result->homeTeam?->name ?? ('Team #' . $result->home_team_id) }}
                                    vs
                                    {{ $result->awayTeam?->name ?? ('Team #' . $result->away_team_id) }}
                                </div>
                            </td>
                            <td class="welcome-result-score-col welcome-odds">{{ $result->results }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endif
