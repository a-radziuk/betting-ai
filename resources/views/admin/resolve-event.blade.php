@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Resolve Event') }}</h1>
        <p class="admin-page-meta">
            {{ __('Unresolved events that started more than 2 hours ago.') }}
        </p>

        @if (session('status'))
            <p class="admin-flash admin-flash--success" role="status">{{ session('status') }}</p>
        @endif

        @if ($events->isEmpty())
            <p class="admin-empty">{{ __('No events ready to resolve.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Start') }}</th>
                            <th>{{ __('Tournament') }}</th>
                            <th>{{ __('Match') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Score') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td class="admin-table-nowrap">
                                    <time datetime="{{ $event->start_time->toIso8601String() }}">
                                        {{ $event->start_time->timezone($timezone)->format('Y-m-d H:i') }}
                                    </time>
                                </td>
                                <td>{{ $event->tournament?->name ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('events.show', $event) }}" class="admin-table-link">
                                        {{ $event->homeTeam?->resolvedDisplayName() ?? ('Team #' . $event->home_team_id) }}
                                        vs
                                        {{ $event->awayTeam?->resolvedDisplayName() ?? ('Team #' . $event->away_team_id) }}
                                    </a>
                                </td>
                                <td>{{ $event->status }}</td>
                                <td>{{ filled($event->score) ? $event->score : '—' }}</td>
                                <td class="admin-table-actions">
                                    <a
                                        href="{{ route('admin.resolve-event.show', $event) }}"
                                        class="btn btn-primary btn-sm"
                                    >{{ __('Resolve') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
