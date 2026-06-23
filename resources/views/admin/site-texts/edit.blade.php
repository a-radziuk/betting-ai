@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Edit site text') }}</h1>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.site-texts.update', $text) }}" class="admin-upload-form">
            @csrf
            @method('PUT')
            @include('admin.site-texts._form', ['text' => $text])
            <div class="admin-form-actions">
                <a href="{{ route('admin.site-texts') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Save') }}</button>
            </div>
        </form>

        @if (auth()->user()->canDeleteInAdmin())
        <form
            method="post"
            action="{{ route('admin.site-texts.destroy', $text) }}"
            class="admin-upload-form"
            style="margin-top: 1rem;"
            onsubmit="return confirm(@json(__('Delete this site text?')))"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary">{{ __('Delete') }}</button>
        </form>
        @endif
    </section>
@endsection
