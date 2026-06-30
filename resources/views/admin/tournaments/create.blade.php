@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('New tournament') }}</h1>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.tournaments.store') }}" class="admin-upload-form">
            @csrf
            @include('admin.tournaments._form')
            <div class="admin-form-actions">
                <a href="{{ route('admin.tournaments') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Create') }}</button>
            </div>
        </form>
    </section>
@endsection
