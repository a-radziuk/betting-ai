<label class="admin-upload-label" for="meta_title">{{ __('Meta title') }}</label>
<input
    type="text"
    id="meta_title"
    name="meta_title"
    class="admin-upload-input"
    value="{{ old('meta_title', $record->meta_title ?? null) }}"
>
<p class="admin-upload-hint">{{ __('Browser tab title and search result title. Use :app for the site name.', ['app' => ':app']) }}</p>

<label class="admin-upload-label" for="meta_description">{{ __('Meta description') }}</label>
<textarea
    id="meta_description"
    name="meta_description"
    class="admin-upload-textarea admin-upload-textarea--compact"
    rows="3"
    maxlength="320"
>{{ old('meta_description', $record->meta_description ?? null) }}</textarea>

<label class="admin-upload-label" for="og_title">{{ __('Open Graph title') }}</label>
<input
    type="text"
    id="og_title"
    name="og_title"
    class="admin-upload-input"
    value="{{ old('og_title', $record->og_title ?? null) }}"
>

<label class="admin-upload-label" for="og_description">{{ __('Open Graph description') }}</label>
<textarea
    id="og_description"
    name="og_description"
    class="admin-upload-textarea admin-upload-textarea--compact"
    rows="3"
    maxlength="320"
>{{ old('og_description', $record->og_description ?? null) }}</textarea>
