<label class="admin-upload-label" for="name">{{ __('Name') }}</label>
<input
    type="text"
    id="name"
    name="name"
    class="admin-upload-input"
    value="{{ old('name', $user->name) }}"
>

<label class="admin-upload-label" for="tagline">{{ __('Tagline') }}</label>
<input
    type="text"
    id="tagline"
    name="tagline"
    class="admin-upload-input"
    value="{{ old('tagline', $user->tagline) }}"
    maxlength="120"
>

<label class="admin-upload-label" for="city">{{ __('City') }}</label>
<input
    type="text"
    id="city"
    name="city"
    class="admin-upload-input"
    value="{{ old('city', $user->city) }}"
    maxlength="120"
>

<label class="admin-upload-label" for="country">{{ __('Country') }}</label>
<input
    type="text"
    id="country"
    name="country"
    class="admin-upload-input"
    value="{{ old('country', $user->country) }}"
    maxlength="120"
>

<label class="admin-upload-label" for="bio">{{ __('Bio') }}</label>
<textarea
    id="bio"
    name="bio"
    class="admin-upload-textarea admin-upload-textarea--compact"
    rows="6"
    maxlength="5000"
>{{ old('bio', $user->bio) }}</textarea>

<label class="admin-upload-label" for="hidden_description">{{ __('Hidden description') }}</label>
<textarea
    id="hidden_description"
    name="hidden_description"
    class="admin-upload-textarea admin-upload-textarea--compact"
    rows="4"
    maxlength="5000"
>{{ old('hidden_description', $user->hidden_description) }}</textarea>
<p class="admin-upload-hint">{{ __('Internal admin notes only. Not shown on public profile pages.') }}</p>
