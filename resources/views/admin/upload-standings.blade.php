@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Import standings') }}</h1>
        <p class="admin-page-meta">
            {{ __('Upload or paste a standings export JSON object (same format as standings:export).') }}
        </p>

        @if (session('status'))
            <p class="admin-flash admin-flash--success" role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.upload-standings.store') }}" class="admin-upload-form" enctype="multipart/form-data">
            @csrf
            <label class="admin-upload-label" for="file">{{ __('Standings JSON file') }}</label>
            <input
                id="file"
                name="file"
                type="file"
                accept=".json,application/json,text/plain"
                class="admin-upload-input"
            >
            <p class="admin-upload-hint">{{ __('Optional. Upload a .json file exported with standings:export.') }}</p>

            <label class="admin-upload-label" for="payload">{{ __('Or paste JSON') }}</label>
            <textarea
                id="payload"
                name="payload"
                class="admin-upload-textarea"
                rows="24"
                spellcheck="false"
            >{{ old('payload') }}</textarea>
            <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Import') }}</button>
        </form>
    </section>
@endsection
