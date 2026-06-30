@php
    $tournament = $tournament ?? null;
@endphp

<label class="admin-upload-label" for="name">{{ __('Name') }}</label>
<input
    type="text"
    id="name"
    name="name"
    class="admin-upload-input"
    value="{{ old('name', $tournament?->name) }}"
    required
>

<label class="admin-upload-label" for="rank">{{ __('Rank') }}</label>
<input
    type="number"
    id="rank"
    name="rank"
    class="admin-upload-input"
    value="{{ old('rank', $tournament?->rank) }}"
    min="0"
    step="1"
    inputmode="numeric"
>
<p class="admin-upload-hint">{{ __('Lower rank appears first on the home page.') }}</p>

<label class="admin-upload-label" for="country">{{ __('Country') }}</label>
<input
    type="text"
    id="country"
    name="country"
    class="admin-upload-input"
    value="{{ old('country', $tournament?->country) }}"
>

<label class="admin-upload-label">
    <input
        type="checkbox"
        name="is_playoff"
        value="1"
        @checked(old('is_playoff', $tournament?->is_playoff ?? false))
    >
    {{ __('Playoff tournament') }}
</label>

<label class="admin-upload-label" for="stoiximan_url">{{ __('Stoiximan URL') }}</label>
<input
    type="url"
    id="stoiximan_url"
    name="stoiximan_url"
    class="admin-upload-input"
    value="{{ old('stoiximan_url', $tournament?->stoiximan_url) }}"
>

<label class="admin-upload-label" for="guardian_standings_url">{{ __('Guardian standings URL') }}</label>
<input
    type="url"
    id="guardian_standings_url"
    name="guardian_standings_url"
    class="admin-upload-input"
    value="{{ old('guardian_standings_url', $tournament?->guardian_standings_url) }}"
>

<label class="admin-upload-label" for="guardian_results_url">{{ __('Guardian results URL') }}</label>
<input
    type="url"
    id="guardian_results_url"
    name="guardian_results_url"
    class="admin-upload-input"
    value="{{ old('guardian_results_url', $tournament?->guardian_results_url) }}"
>
