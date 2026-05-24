@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('User Bets') }}</h1>
        <p class="admin-page-meta">
            {{ __('All active (pending) user bets, ordered by nearest event kickoff.') }}
        </p>

        @if (session('status'))
            <p class="admin-flash admin-flash--success" role="status">{{ session('status') }}</p>
        @endif

        @if ($bets->isEmpty())
            <p class="admin-empty">{{ __('No active user bets.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Event') }}</th>
                            <th>{{ __('Kickoff') }}</th>
                            <th>{{ __('Selection') }}</th>
                            <th class="admin-table-num">{{ __('Odds') }}</th>
                            <th class="admin-table-num">{{ __('Stake') }}</th>
                            <th class="admin-table-num">{{ __('Potential') }}</th>
                            <th>{{ __('Placed') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bets as $bet)
                            @php
                                $event = $bet->event;
                                $selection = $bet->odd?->selection?->name ?? '—';
                                $market = $bet->odd?->selection?->market?->type;
                                $betLabel = $market ? "{$selection} ({$market})" : $selection;
                            @endphp
                            <tr>
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
                                            {{ $event->homeTeam?->resolvedDisplayName() ?? ('Team #' . $event->home_team_id) }}
                                            vs
                                            {{ $event->awayTeam?->resolvedDisplayName() ?? ('Team #' . $event->away_team_id) }}
                                        </a>
                                        @if ($event->tournament?->name)
                                            <div class="admin-table-sub">{{ $event->tournament->name }}</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="admin-table-nowrap">
                                    @if ($event?->start_time)
                                        <time datetime="{{ $event->start_time->toIso8601String() }}">
                                            {{ $event->start_time->timezone($timezone)->format('Y-m-d H:i') }}
                                        </time>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $betLabel }}</td>
                                <td class="admin-table-num">{{ number_format((float) $bet->odds_at_bet, 2) }}</td>
                                <td class="admin-table-num">{{ number_format((float) $bet->stake, 2) }}</td>
                                <td class="admin-table-num">{{ number_format((float) $bet->potential_return, 2) }}</td>
                                <td class="admin-table-nowrap">
                                    {{ $bet->created_at?->timezone($timezone)->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="admin-table-actions">
                                    <form
                                        method="post"
                                        action="{{ route('admin.user-bets.destroy', $bet) }}"
                                        onsubmit="return confirm(@js(__('Delete this bet and revert wallet changes?')))"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                            @if (filled($bet->explanation))
                                <tr>
                                    <td colspan="9" class="admin-table-explanation">{{ $bet->explanation }}</td>
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
