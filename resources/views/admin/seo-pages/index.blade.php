@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('SEO Pages') }}</h1>
                <p class="admin-page-meta">{{ __('Manage SEO metadata for fixed public pages.') }}</p>
            </div>
        </div>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>{{ __('Page') }}</th>
                        <th>{{ __('Key') }}</th>
                        <th>{{ __('Updated') }}</th>
                        <th class="admin-table-actions">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <td>{{ $page->label }}</td>
                            <td><code>{{ $page->key }}</code></td>
                            <td class="admin-table-nowrap">{{ $page->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="admin-table-actions">
                                <a href="{{ route('admin.seo-pages.edit', $page) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
