@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">{{ __('Blog') }}</h1>
                <p class="admin-page-meta">{{ __('Manage blog posts published at /blog.') }}</p>
            </div>
            <div class="admin-form-actions">
                <a href="{{ route('admin.blogs.create-from-json') }}" class="btn btn-secondary">{{ __('Create from JSON') }}</a>
                <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary">{{ __('New post') }}</a>
            </div>
        </div>

        @if (session('status'))
            <p class="admin-flash admin-flash--success">{{ session('status') }}</p>
        @endif

        @if ($posts->isEmpty())
            <p class="admin-empty">{{ __('No blog posts yet.') }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Title') }}</th>
                            <th>{{ __('Author') }}</th>
                            <th>{{ __('Published') }}</th>
                            <th class="admin-table-actions">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td>
                                    {{ $post->title }}
                                    <div class="admin-table-sub"><code>{{ $post->slug }}</code></div>
                                </td>
                                <td>{{ $post->author }}</td>
                                <td class="admin-table-nowrap">{{ $post->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('blog.show', $post->slug) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">{{ __('View') }}</a>
                                    <a href="{{ route('admin.blogs.edit', $post) }}" class="btn btn-primary btn-sm">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
