@extends('layouts.admin')

@section('content')
    <section class="card card-pad">
        <h1 class="admin-page-title">{{ __('Edit user') }}</h1>
        <p class="admin-page-meta">{{ $user->name }} · {{ $user->email }}</p>

        @if ($errors->any())
            <ul class="admin-flash admin-flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('admin.users.update', $user) }}" class="admin-upload-form" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.users._form', [
                'user' => $user,
                'privelegeOptions' => $privelegeOptions,
                'selectedPriveleges' => $selectedPriveleges,
            ])
            <div class="admin-form-actions">
                <a href="{{ route('admin.users') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Save') }}</button>
            </div>
        </form>

        @if (auth()->id() !== $user->id)
            <form
                method="post"
                action="{{ route('admin.users.destroy', $user) }}"
                class="admin-delete-form"
                onsubmit="return confirm(@json(__('Delete this user?')))"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary">{{ __('Delete') }}</button>
            </form>
        @endif
    </section>
@endsection
