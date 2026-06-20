@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('Legal Pages') }}</h1>
                <p class="admin-page-meta">{{ __('Manage public legal pages, the FAQ at /faq, and the subscription checkout terms shown at /subscribe/terms.') }}</p>
            </div>
            <a href="{{ route('admin.legal-pages.create') }}" class="btn btn-primary">{{ __('New page') }}</a>
        </div>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        @if ($pages->isEmpty())
            <p class="admin-empty">{{ __('No legal pages yet.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Slug') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th class="admin-table-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td>
                                    {{ $page->title }}
                                    @if ($page->slug === config('subscriptions.terms.slug'))
                                        <div class="admin-table-sub">{{ __('Subscription checkout terms') }}</div>
                                    @elseif ($page->slug === config('legal.faq.slug'))
                                        <div class="admin-table-sub">{{ __('Site FAQ (/faq)') }}</div>
                                    @endif
                                </td>
                                <td><code>{{ $page->slug }}</code></td>
                                <td class="admin-table-nowrap">{{ $page->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('legal.show', $page->slug) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">{{ __('View') }}</a>
                                    <a href="{{ route('admin.legal-pages.edit', $page) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
