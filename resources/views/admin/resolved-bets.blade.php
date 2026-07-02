@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Resolved Bets') }}</h1>
        <p class="admin-page-meta">
            {{ __('All settled user bets, newest first.') }}
        </p>

        <form method="get" action="{{ route('admin.resolved-bets') }}" class="admin-upload-form" style="margin-bottom: 1.25rem;">
            <label class="admin-upload-label" for="search">{{ __('Search by event name') }}</label>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <input
                    type="search"
                    id="search"
                    name="search"
                    class="admin-upload-input"
                    value="{{ $search }}"
                    placeholder="{{ __('Team name match…') }}"
                    style="flex: 1; min-width: 12rem;"
                >
                <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                @if ($search !== '')
                    <a href="{{ route('admin.resolved-bets') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
                @endif
            </div>
        </form>

        @if ($bets->isEmpty())
            <p class="admin-empty">
                @if ($search !== '')
                    {{ __('No resolved bets match your search.') }}
                @else
                    {{ __('No resolved bets yet.') }}
                @endif
            </p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Event') }}</th>
                            <th>{{ __('Score') }}</th>
                            <th>{{ __('Kickoff') }}</th>
                            <th>{{ __('Selection') }}</th>
                            <th class="admin-table-num">{{ __('Odds') }}</th>
                            <th class="admin-table-num">{{ __('Stake') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="admin-table-num">{{ __('Won/Lost') }}</th>
                            <th class="admin-table-num">{{ __('Resolved #') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bets as $bet)
                            @php
                                $event = $bet->event;
                            @endphp
                            <tr>
                                <td class="admin-table-nowrap">{{ $bet->id }}</td>
                                <td>
                                    @if ($bet->user)
                                        <a href="{{ route('players.show', $bet->user) }}" class="admin-table-link">
                                            {{ $bet->user->name }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($event)
                                        <a href="{{ route('events.show', $event) }}" class="admin-table-link">
                                            {{ \App\Support\PlayerResolvedBets::eventName($bet) }}
                                        </a>
                                        @if ($event->tournament?->name)
                                            <div class="admin-table-sub">{{ $event->tournament->name }}</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ \App\Support\EventScoreDisplay::forEvent($event) }}</td>
                                <td class="admin-table-nowrap">
                                    @if ($event?->start_time)
                                        <time datetime="{{ $event->start_time->toIso8601String() }}">
                                            {{ $event->start_time->timezone($timezone)->format('Y-m-d H:i') }}
                                        </time>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ \App\Support\PlayerResolvedBets::betLabel($bet) }}</td>
                                <td class="admin-table-num">{{ number_format((float) $bet->odds_at_bet, 2) }}</td>
                                <td class="admin-table-num">{{ number_format((float) $bet->stake, 2) }}</td>
                                <td>{{ $bet->statusLabel() }}</td>
                                <td class="admin-table-num">{{ number_format(\App\Support\PlayerResolvedBets::wonLostAmount($bet), 2) }}</td>
                                <td class="admin-table-num">{{ $bet->resolved_order ?? '—' }}</td>
                            </tr>
                            @if (filled($bet->explanation))
                                <tr>
                                    <td colspan="11" class="admin-table-explanation">{{ $bet->explanation }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($bets->hasPages())
                <div class="admin-pagination">
                    {{ $bets->links() }}
                </div>
            @endif
        @endif
    </section>
@endsection
