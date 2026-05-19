@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <p class="admin-back-link">
            <a href="{{ route('admin.resolve-event') }}">&larr; {{ __('Back to resolve list') }}</a>
        </p>

        <h1 class="admin-page-title">{{ __('Resolve Event') }}</h1>

        <p class="admin-page-meta">
            {{ $event->homeTeam?->resolvedDisplayName() ?? ('Team #' . $event->home_team_id) }}
            vs
            {{ $event->awayTeam?->resolvedDisplayName() ?? ('Team #' . $event->away_team_id) }}
            &middot;
            <time datetime="{{ $event->start_time->toIso8601String() }}">
                {{ $event->start_time->timezone($timezone)->format('Y-m-d H:i') }}
            </time>
            @if ($event->tournament?->name)
                &middot; {{ $event->tournament->name }}
            @endif
        </p>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form
            method="post"
            action="{{ route('admin.resolve-event.store', $event) }}"
            class="admin-resolve-form"
        >
            @csrf
            <label class="admin-upload-label" for="score">{{ __('Final score') }}</label>
            <input
                type="text"
                id="score"
                name="score"
                class="admin-resolve-score-input"
                value="{{ old('score') }}"
                placeholder="2:2"
                autocomplete="off"
                inputmode="numeric"
            >
            <p class="admin-page-meta">{{ __('Use home:away format, e.g. 2:3 or 2-3.') }}</p>
            <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Submit') }}</button>
        </form>
    </section>
@endsection
