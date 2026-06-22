@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Edit blog post') }}</h1>
        <p class="admin-page-meta">{{ $post->title }}</p>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.blogs.update', $post) }}" class="admin-upload-form">
            @csrf
            @method('PUT')
            @include('admin.blogs._form', ['post' => $post])
            <div class="admin-form-actions">
                <a href="{{ route('admin.blogs') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Save') }}</button>
            </div>
        </form>

        <form
            method="post"
            action="{{ route('admin.blogs.destroy', $post) }}"
            class="admin-delete-form"
            onsubmit="return confirm(@json(__('Delete this blog post?')))"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary">{{ __('Delete') }}</button>
        </form>
    </section>
@endsection
