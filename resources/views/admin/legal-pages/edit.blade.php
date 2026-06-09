@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Edit legal page') }}</h1>
        <p class="admin-page-meta">{{ $page->title }}</p>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.legal-pages.update', $page) }}" class="admin-upload-form">
            @csrf
            @method('PUT')
            @include('admin.legal-pages._form', ['page' => $page])
            <div class="admin-form-actions">
                <a href="{{ route('admin.legal-pages') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Save') }}</button>
            </div>
        </form>

        <form
            method="post"
            action="{{ route('admin.legal-pages.destroy', $page) }}"
            class="admin-delete-form"
            onsubmit="return confirm(@json(__('Delete this legal page?')))"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary">{{ __('Delete') }}</button>
        </form>
    </section>
@endsection
