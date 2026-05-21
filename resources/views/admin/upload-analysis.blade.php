@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Upload Analysis') }}</h1>
        <p class="admin-page-meta">
            {{ __('Paste a JSON array of event analysis objects (same format as event:import-analysis).') }}
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

        <form method="post" action="{{ route('admin.upload-analysis.store') }}" class="admin-upload-form">
            @csrf
            <label class="admin-upload-label" for="payload">{{ __('Event analysis JSON') }}</label>
            <textarea
                id="payload"
                name="payload"
                class="admin-upload-textarea"
                rows="24"
                spellcheck="false"
            >{{ old('payload') }}</textarea>
            <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Submit') }}</button>
        </form>
    </section>
@endsection
