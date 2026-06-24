@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('User Texts') }}</h1>
                <p class="admin-page-meta">{{ __('Edit public profile copy and internal notes for users.') }}</p>
            </div>
        </div>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        <form method="get" action="{{ route('admin.user-texts') }}" class="admin-upload-form" style="margin-bottom: 1.25rem;">
            <label class="admin-upload-label" for="search">{{ __('Search by name') }}</label>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <input
                    type="search"
                    id="search"
                    name="search"
                    class="admin-upload-input"
                    value="{{ $search }}"
                    placeholder="{{ __('Partial name match…') }}"
                    style="flex: 1; min-width: 12rem;"
                >
                <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                @if ($search !== '')
                    <a href="{{ route('admin.user-texts') }}" class="btn btn-secondary">{{ __('Clear') }}</a>
                @endif
            </div>
        </form>

        @if ($users->isEmpty())
            <p class="admin-empty">
                @if ($search !== '')
                    {{ __('No users match your search.') }}
                @else
                    {{ __('No users yet.') }}
                @endif
            </p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Tagline') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th class="admin-table-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="admin-table-nowrap">{{ $user->id }}</td>
                                <td>{{ $user->name ?: '—' }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->tagline ?: '—' }}</td>
                                <td class="admin-table-nowrap">{{ $user->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.user-texts.edit', $user) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
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
