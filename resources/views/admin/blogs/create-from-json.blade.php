@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Create from JSON') }}</h1>
        <p class="admin-page-meta">{{ __('Paste a JSON object with blog post fields to publish immediately.') }}</p>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.blogs.store-from-json') }}" class="admin-upload-form">
            @csrf

            <label class="admin-upload-label" for="json">{{ __('Blog post JSON') }}</label>
            <p class="admin-upload-hint">
                {{ __('Required keys: title, author, body (or text). Optional: slug, meta_title, meta_description, og_title, og_description.') }}
            </p>
            <textarea
                id="json"
                name="json"
                class="admin-upload-textarea"
                rows="24"
                spellcheck="false"
                required
            >{{ old('json') }}</textarea>

            <div class="admin-form-actions">
                <a href="{{ route('admin.blogs') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Publish') }}</button>
            </div>
        </form>
    </section>
@endsection
