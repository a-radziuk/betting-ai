@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('Tournaments') }}</h1>
                <p class="admin-page-meta">{{ __('Create, edit, and remove tournaments.') }}</p>
            </div>
            <a href="{{ route('admin.tournaments.create') }}" class="btn btn-primary">{{ __('New tournament') }}</a>
        </div>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        @if ($tournaments->isEmpty())
            <p class="admin-empty">{{ __('No tournaments yet.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Country') }}</th>
                            <th>{{ __('Source') }}</th>
                            <th class="admin-table-num">{{ __('Rank') }}</th>
                            <th>{{ __('Playoff') }}</th>
                            <th class="admin-table-num">{{ __('Teams') }}</th>
                            <th class="admin-table-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tournaments as $tournament)
                            <tr>
                                <td class="admin-table-nowrap">{{ $tournament->id }}</td>
                                <td>
                                    <a href="{{ route('tournaments.show', $tournament) }}" class="admin-table-link" target="_blank" rel="noopener">
                                        {{ $tournament->name }}
                                    </a>
                                </td>
                                <td>{{ $tournament->country ?: '—' }}</td>
                                <td>{{ $tournament->source ?: '—' }}</td>
                                <td class="admin-table-num">{{ $tournament->rank ?? '—' }}</td>
                                <td>{{ $tournament->is_playoff ? __('Yes') : __('No') }}</td>
                                <td class="admin-table-num">{{ $tournament->teams_count }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="admin-pagination">
                {{ $tournaments->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </section>
@endsection
