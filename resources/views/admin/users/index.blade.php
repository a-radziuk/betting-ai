@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('Users') }}</h1>
                <p class="admin-page-meta">{{ __('Create, edit, and remove user accounts.') }}</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">{{ __('New user') }}</a>
        </div>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        @if ($users->isEmpty())
            <p class="admin-empty">{{ __('No users yet.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Role') }}</th>
                            <th>{{ __('Metrics') }}</th>
                            <th>{{ __('Privileges') }}</th>
                            <th>{{ __('Created') }}</th>
                            <th class="admin-table-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="admin-table-nowrap">{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>
                                    {{ $user->email }}
                                    @if ($user->email_verified_at)
                                        <div class="admin-table-sub">{{ __('Verified') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($user->is_superadmin)
                                        {{ __('Superadmin') }}
                                    @elseif ($user->is_hidden)
                                        {{ __('Hidden') }}
                                    @else
                                        {{ __('User') }}
                                    @endif
                                </td>
                                <td>
                                    <input
                                        type="checkbox"
                                        class="admin-table-checkbox"
                                        data-admin-metrics-toggle
                                        data-url="{{ route('admin.users.metrics-availability', $user) }}"
                                        @checked($user->is_metrics_available)
                                        aria-label="{{ __('Metrics available for :name', ['name' => $user->name]) }}"
                                    >
                                </td>
                                <td><code>{{ $user->priveleges ?: '—' }}</code></td>
                                <td class="admin-table-nowrap">{{ $user->created_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="admin-pagination">
                {{ $users->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    @vite(['resources/js/admin-users-metrics.js'])
@endpush
