@if (count($standingsRows) === 0)
    <div class="empty">{{ __('No standings data yet. Run') }} <code class="tabular-nums">php artisan guardian:standings {{ $tournament->id }}</code> {{ __('after setting') }} <code>guardian_standings_url</code> {{ __('on this tournament.') }}</div>
@else
    <p class="meta">{{ __('League standings') }}</p>
    @if ($tournament->standings_updated_at)
        <p class="meta" style="margin-top: 0.5rem;">
            {{ __('Updated') }} {{ $tournament->standings_updated_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
        </p>
    @endif
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Team') }}</th>
                    <th class="text-right">P</th>
                    <th class="text-right">W</th>
                    <th class="text-right">D</th>
                    <th class="text-right">L</th>
                    <th class="text-right">GF</th>
                    <th class="text-right">GA</th>
                    <th class="text-right">GD</th>
                    <th class="text-right">Pts</th>
                    <th>{{ __('Form') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($standingsRows as $row)
                    @php
                        $pos = $row['position'] ?? null;
                        $zone = $pos !== null && $pos !== ''
                            ? ($standingsPromrel[(string) $pos] ?? null)
                            : null;
                        $zoneRowClass = '';
                        $zonePosBadgeClass = '';
                        $zoneTitle = '';
                        if (is_array($zone)) {
                            $zoneTitle = (string) ($zone['name'] ?? '');
                            $zType = $zone['type'] ?? '';
                            $zSubtype = (string) ($zone['subtype'] ?? '');
                            if ($zType === 'promotion') {
                                $zonePosBadgeClass = match ($zSubtype) {
                                    'champions-league' => 'cl',
                                    'europa-league' => 'el',
                                    'conference-league' => 'cel',
                                    default => 'promo-other',
                                };
                                $zoneRowClass = match ($zSubtype) {
                                    'champions-league' => 'standings-row--promotion standings-row--promotion-cl',
                                    'europa-league' => 'standings-row--promotion standings-row--promotion-el',
                                    'conference-league' => 'standings-row--promotion standings-row--promotion-cel',
                                    default => 'standings-row--promotion standings-row--promotion-other',
                                };
                            } elseif ($zType === 'relegation') {
                                $zonePosBadgeClass = 'rel';
                                $zoneRowClass = 'standings-row--relegation';
                            }
                        }
                    @endphp
                    <tr class="{{ $zoneRowClass }}">
                        <td class="tabular-nums standings-pos-cell">
                            @if ($zonePosBadgeClass !== '')
                                <span
                                    class="standings-pos-badge standings-pos-badge--{{ $zonePosBadgeClass }}"
                                    title="{{ e($zoneTitle) }}"
                                >{{ $row['position'] ?? '—' }}</span>
                            @else
                                {{ $row['position'] ?? '—' }}
                            @endif
                        </td>
                        <td class="standings-team-cell">
                            {{ $row['team_display_name'] ?? $row['team'] ?? '—' }}
                            @php
                                $movement = $row['movement'] ?? null;
                            @endphp
                            @if ($movement === 'up')
                                <span
                                    class="standings-movement standings-movement--up"
                                    role="img"
                                    aria-label="{{ __('Moved up since last update') }}"
                                >↑</span>
                            @elseif ($movement === 'down')
                                <span
                                    class="standings-movement standings-movement--down"
                                    role="img"
                                    aria-label="{{ __('Moved down since last update') }}"
                                >↓</span>
                            @endif
                        </td>
                        <td class="text-right tabular-nums">{{ $row['played'] ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $row['won'] ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $row['drawn'] ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $row['lost'] ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $row['goals_for'] ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $row['goals_against'] ?? '—' }}</td>
                        <td class="text-right tabular-nums">{{ $row['goal_difference'] ?? '—' }}</td>
                        <td class="text-right tabular-nums"><strong>{{ $row['points'] ?? '—' }}</strong></td>
                        <td class="form-icons-cell">
                            @php
                                $formSegments = \App\Support\GuardianFormIcons::parseSegments($row['form'] ?? null);
                            @endphp
                            @if (count($formSegments) > 0)
                                @foreach ($formSegments as $seg)
                                    <span
                                        class="form-icon form-icon--{{ strtolower($seg['letter']) }}"
                                        title="{{ e($seg['tooltip']) }}"
                                    >{{ $seg['letter'] }}</span>
                                @endforeach
                            @else
                                <span style="color: var(--muted); font-size: 0.85rem;" title="{{ e($row['form'] ?? '') }}">{{ \Illuminate\Support\Str::limit($row['form'] ?? '—', 48) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
