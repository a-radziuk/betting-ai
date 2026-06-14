@if (count($standingsRows) === 0 && count($standingsGroups ?? []) === 0)
    <div class="empty">{{ __('No standings data yet. Run') }} <code class="tabular-nums">php artisan guardian:standings {{ $tournament->id }}</code> {{ __('or') }} <code class="tabular-nums">php artisan guardian:standings-multi {{ $tournament->id }}</code> {{ __('after setting') }} <code>guardian_standings_url</code> {{ __('on this tournament.') }}</div>
@else
    <p class="meta">{{ count($standingsGroups ?? []) > 0 ? __('Group standings') : __('League standings') }}</p>
    @if ($tournament->standings_updated_at)
        <p class="meta" style="margin-top: 0.5rem;">
            {{ __('Updated') }} {{ $tournament->standings_updated_at->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
        </p>
    @endif


    @if (count($standingsGroups ?? []) > 0)
        <div class="standings-groups">
            @foreach ($standingsGroups as $group)
                <section class="standings-group" aria-label="{{ $group['name'] }}">
                    <h3 class="standings-group-title">{{ $group['name'] }}</h3>
                    @include('partials.tournament-standings-table-body', [
                        'standingsRows' => $group['rows'],
                        'standingsPromrel' => $standingsPromrel,
                    ])
                </section>
            @endforeach
        </div>
    @else
        <div class="overflow-x-auto">
            @include('partials.tournament-standings-table-body', [
                'standingsRows' => $standingsRows,
                'standingsPromrel' => $standingsPromrel,
            ])
        </div>
    @endif
@endif
