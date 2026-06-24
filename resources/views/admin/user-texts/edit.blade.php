@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Edit user texts') }}</h1>
        <p class="admin-page-meta">{{ $user->email }}</p>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.user-texts.update', $user) }}" class="admin-upload-form">
            @csrf
            @method('PUT')
            @include('admin.user-texts._form', ['user' => $user])
            <div class="admin-form-actions">
                <a href="{{ route('admin.user-texts') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Save') }}</button>
            </div>
        </form>
    </section>
@endsection
