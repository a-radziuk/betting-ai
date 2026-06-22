@php
    $text = $text ?? null;
@endphp

<label class="admin-upload-label" for="label">{{ __('Label') }}</label>
<input
    type="text"
    id="label"
    name="label"
    class="admin-upload-input"
    value="{{ old('label', $text?->label) }}"
    required
>

<label class="admin-upload-label" for="key">{{ __('Key') }}</label>
<input
    type="text"
    id="key"
    name="key"
    class="admin-upload-input"
    value="{{ old('key', $text?->key) }}"
    pattern="[a-z0-9]+(?:[._-][a-z0-9]+)*"
    @readonly($text !== null)
    required
>
@if ($text)
    <p class="admin-upload-hint">{{ __('The key cannot be changed after creation.') }}</p>
@else
    <p class="admin-upload-hint">{{ __('Lowercase letters, numbers, dots, underscores, and hyphens only.') }}</p>
@endif

<label class="admin-upload-label" for="group">{{ __('Group') }}</label>
<input
    type="text"
    id="group"
    name="group"
    class="admin-upload-input"
    value="{{ old('group', $text?->group) }}"
    placeholder="{{ __('home, header, footer') }}"
>

<label class="admin-upload-label" for="value">{{ __('Text') }}</label>
<textarea
    id="value"
    name="value"
    class="admin-upload-textarea"
    rows="6"
    required
>{{ old('value', $text?->value) }}</textarea>
<p class="admin-upload-hint">{{ __('Use :app for the site name where needed.', ['app' => ':app']) }}</p>
