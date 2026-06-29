@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('Prediction Subscriptions') }}</h1>
                <p class="admin-page-meta">{{ __('Manage which users receive automated bets for each prediction type.') }}</p>
            </div>
            <a href="{{ route('admin.prediction-subscriptions.create') }}" class="btn btn-primary">{{ __('New subscription') }}</a>
        </div>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        @if ($subscriptions->isEmpty())
            <p class="admin-empty">{{ __('No prediction subscriptions yet.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('User ID') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Prediction type') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th class="admin-table-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td class="admin-table-nowrap">{{ $subscription->id }}</td>
                                <td class="admin-table-nowrap">{{ $subscription->user_id }}</td>
                                <td>
                                    @if ($subscription->user)
                                        <a href="{{ route('players.show', $subscription->user) }}" class="admin-table-link">
                                            {{ $subscription->user->name }}
                                        </a>
                                        <div class="admin-table-sub">{{ $subscription->user->email }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><code>{{ $subscription->prediction_type }}</code></td>
                                <td class="admin-table-nowrap">{{ $subscription->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.prediction-subscriptions.edit', $subscription) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="admin-pagination">
                {{ $subscriptions->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </section>
@endsection
