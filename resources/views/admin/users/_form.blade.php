@php
    $user = $user ?? null;
    $selectedPriveleges = is_array(old('priveleges'))
        ? old('priveleges')
        : ($selectedPriveleges ?? []);
@endphp

<label class="admin-upload-label" for="avatar">{{ __('Avatar') }}</label>
<div class="admin-avatar-field">
    @if ($avatarUrl = $user?->profileAvatarUrl())
        <img src="{{ $avatarUrl }}" alt="" class="admin-avatar-preview">
    @else
        <div class="admin-avatar-preview admin-avatar-preview--empty" aria-hidden="true">{{ __('None') }}</div>
    @endif
    <div class="admin-avatar-field-input">
        <input
            type="file"
            id="avatar"
            name="avatar"
            class="admin-upload-input"
            accept="image/jpeg,image/png,image/gif,image/webp"
        >
        <p class="admin-upload-hint">{{ __('JPEG, PNG, GIF or WebP, up to 2 MB.') }}</p>
        @error('avatar')
            <p class="admin-upload-hint admin-upload-hint--error">{{ $message }}</p>
        @enderror
    </div>
</div>

<label class="admin-upload-label" for="name">{{ __('Name') }}</label>
<input
    type="text"
    id="name"
    name="name"
    class="admin-upload-input"
    value="{{ old('name', $user?->name) }}"
    required
>

<label class="admin-upload-label" for="email">{{ __('Email') }}</label>
<input
    type="email"
    id="email"
    name="email"
    class="admin-upload-input"
    value="{{ old('email', $user?->email) }}"
    required
>

<label class="admin-upload-label" for="password">{{ $user ? __('New password') : __('Password') }}</label>
<input
    type="password"
    id="password"
    name="password"
    class="admin-upload-input"
    @if ($user === null) required @endif
    autocomplete="new-password"
>
@if ($user)
    <p class="admin-upload-hint">{{ __('Leave blank to keep the current password.') }}</p>
@endif

<label class="admin-upload-label" for="password_confirmation">{{ __('Confirm password') }}</label>
<input
    type="password"
    id="password_confirmation"
    name="password_confirmation"
    class="admin-upload-input"
    @if ($user === null) required @endif
    autocomplete="new-password"
>

<label class="admin-upload-checkbox">
    <input
        type="checkbox"
        name="is_superadmin"
        value="1"
        @checked((bool) old('is_superadmin', $user?->is_superadmin))
    >
    <span>{{ __('Superadmin') }}</span>
</label>

<label class="admin-upload-checkbox">
    <input
        type="checkbox"
        name="email_verified"
        value="1"
        @checked((bool) old('email_verified', $user?->email_verified_at !== null))
    >
    <span>{{ __('Email verified') }}</span>
</label>

<fieldset class="admin-upload-fieldset">
    <legend class="admin-upload-label">{{ __('Privileges') }}</legend>
    @foreach ($privelegeOptions as $value => $label)
        <label class="admin-upload-checkbox">
            <input
                type="checkbox"
                name="priveleges[]"
                value="{{ $value }}"
                @checked(in_array($value, $selectedPriveleges, true))
            >
            <span>{{ $label }} <code>{{ $value }}</code></span>
        </label>
    @endforeach
</fieldset>

<label class="admin-upload-label" for="see_tips_expires_at">{{ __('See tips expires at') }}</label>
<input
    type="datetime-local"
    id="see_tips_expires_at"
    name="see_tips_expires_at"
    class="admin-upload-input"
    value="{{ old('see_tips_expires_at', $user?->see_tips_expires_at?->format('Y-m-d\TH:i')) }}"
>

<label class="admin-upload-label" for="tagline">{{ __('Tagline') }}</label>
<input
    type="text"
    id="tagline"
    name="tagline"
    class="admin-upload-input"
    value="{{ old('tagline', $user?->tagline) }}"
>

<label class="admin-upload-label" for="city">{{ __('City') }}</label>
<input
    type="text"
    id="city"
    name="city"
    class="admin-upload-input"
    value="{{ old('city', $user?->city) }}"
>

<label class="admin-upload-label" for="country">{{ __('Country') }}</label>
<input
    type="text"
    id="country"
    name="country"
    class="admin-upload-input"
    value="{{ old('country', $user?->country) }}"
>

<label class="admin-upload-label" for="bio">{{ __('Bio') }}</label>
<textarea
    id="bio"
    name="bio"
    class="admin-upload-textarea admin-upload-textarea--compact"
    rows="6"
>{{ old('bio', $user?->bio) }}</textarea>

<label class="admin-upload-label" for="hidden_description">{{ __('Hidden description') }}</label>
<textarea
    id="hidden_description"
    name="hidden_description"
    class="admin-upload-textarea admin-upload-textarea--compact"
    rows="4"
>{{ old('hidden_description', $user?->hidden_description) }}</textarea>
<p class="admin-upload-hint">{{ __('Internal admin notes only. Not shown on public profile pages.') }}</p>

@if ($user?->provider)
    <p class="admin-upload-hint">{{ __('OAuth provider: :provider (:id)', ['provider' => $user->provider, 'id' => $user->provider_id]) }}</p>
@endif
