@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Edit SEO') }}: {{ $page->label }}</h1>
        <p class="admin-page-meta"><code>{{ $page->key }}</code></p>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.seo-pages.update', $page) }}" class="admin-upload-form">
            @csrf
            @method('PUT')

            <p class="admin-upload-hint">{{ $page->placeholderHint() }}</p>

            @include('admin.partials.seo-fields', ['record' => $page])

            <div class="admin-form-actions">
                <a href="{{ route('admin.seo-pages') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Save') }}</button>
            </div>
        </form>
    </section>
@endsection
