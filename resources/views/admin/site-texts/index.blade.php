@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('Site Texts') }}</h1>
                <p class="admin-page-meta">{{ __('Manage website copy by key.') }}</p>
            </div>
            <a href="{{ route('admin.site-texts.create') }}" class="btn btn-primary">{{ __('New text') }}</a>
        </div>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        @if ($texts->isEmpty())
            <p class="admin-empty">{{ __('No site texts yet.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Label') }}</th>
                            <th>{{ __('Key') }}</th>
                            <th>{{ __('Group') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th class="admin-table-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($texts as $text)
                            <tr>
                                <td>{{ $text->label }}</td>
                                <td><code>{{ $text->key }}</code></td>
                                <td>{{ $text->group ?: '—' }}</td>
                                <td class="admin-table-nowrap">{{ $text->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.site-texts.edit', $text) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
