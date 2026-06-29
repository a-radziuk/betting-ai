@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Edit prediction subscription') }}</h1>
        <p class="admin-page-meta">#{{ $subscription->id }}</p>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.prediction-subscriptions.update', $subscription) }}" class="admin-upload-form">
            @csrf
            @method('PUT')
            @include('admin.prediction-subscriptions._form', ['subscription' => $subscription])
            <div class="admin-form-actions">
                <a href="{{ route('admin.prediction-subscriptions') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Save') }}</button>
            </div>
        </form>

        <form
            method="post"
            action="{{ route('admin.prediction-subscriptions.destroy', $subscription) }}"
            class="admin-delete-form"
            onsubmit="return confirm(@json(__('Delete this prediction subscription?')))"
        >
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary">{{ __('Delete') }}</button>
        </form>
    </section>
@endsection
