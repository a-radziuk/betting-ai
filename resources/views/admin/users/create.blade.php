@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('New user') }}</h1>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.users.store') }}" class="admin-upload-form" enctype="multipart/form-data">
            @csrf
            @include('admin.users._form', ['privelegeOptions' => $privelegeOptions, 'selectedPriveleges' => []])
            <div class="admin-form-actions">
                <a href="{{ route('admin.users') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Create') }}</button>
            </div>
        </form>
    </section>
@endsection
