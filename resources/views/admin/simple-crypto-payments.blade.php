@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Simple Crypto Payments') }}</h1>
        <p class="admin-page-meta">{{ __('Review crypto transfers and activate subscriptions after verification.') }}</p>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        @if ($payments->isEmpty())
            <p class="admin-empty">{{ __('No crypto payments yet.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Plan') }}</th>
                            <th>{{ __('Wallet') }}</th>
                            <th>{{ __('Payment code') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Paid at') }}</th>
                            <th class="admin-table-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="admin-table-nowrap">{{ $payment->id }}</td>
                                <td>
                                    {{ $payment->user?->name ?? '—' }}
                                    <div class="admin-table-sub">{{ $payment->user?->email ?? '' }}</div>
                                </td>
                                <td>{{ $payment->plan_id }}</td>
                                <td>{{ $payment->wallet_label }}</td>
                                <td><code>{{ $payment->payment_code }}</code></td>
                                <td class="admin-table-num">
                                    {{ number_format($payment->amount_cents / 100, 2) }}
                                    {{ strtoupper($payment->currency) }}
                                </td>
                                <td>{{ str_replace('_', ' ', $payment->status) }}</td>
                                <td class="admin-table-nowrap">
                                    {{ $payment->paid_at?->timezone($timezone)->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="admin-table-actions">
                                    @if ($payment->isApprovableByAdmin())
                                        <form method="POST" action="{{ route('admin.simple-crypto-payments.approve', $payment) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm">{{ __('Approve') }}</button>
                                        </form>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="admin-pagination">
                {{ $payments->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </section>
@endsection
