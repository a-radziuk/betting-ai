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

<label class="admin-upload-label" for="source">{{ __('Source') }}</label>
<input
    type="text"
    id="source"
    name="source"
    class="admin-upload-input"
    value="{{ old('source', $tournament?->source) }}"
    placeholder="{{ __('e.g. stoiximan, parimatch') }}"
>

<label class="admin-upload-label" for="export_marker">{{ __('Export marker') }}</label>
<input
    type="text"
    id="export_marker"
    name="export_marker"
    class="admin-upload-input"
    value="{{ old('export_marker', $tournament?->export_marker) }}"
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

<label class="admin-upload-label">
    <input
        type="checkbox"
        name="is_active"
        value="1"
        @checked(old('is_active', $tournament?->is_active ?? true))
    >
    {{ __('Active on site') }}
</label>
<p class="admin-upload-hint">{{ __('Inactive tournaments and their events are hidden from the public site.') }}</p>

<label class="admin-upload-label">
    <input
        type="checkbox"
        name="is_fifa"
        value="1"
        @checked(old('is_fifa', $tournament?->is_fifa ?? false))
    >
    {{ __('FIFA tournament') }}
</label>

<label class="admin-upload-label" for="stoiximan_url">{{ __('Stoiximan URL') }}</label>
<input
    type="url"
    id="stoiximan_url"
    name="stoiximan_url"
    class="admin-upload-input"
    value="{{ old('stoiximan_url', $tournament?->stoiximan_url) }}"
>

<label class="admin-upload-label" for="parimatch_url">{{ __('Parimatch URL') }}</label>
<input
    type="url"
    id="parimatch_url"
    name="parimatch_url"
    class="admin-upload-input"
    value="{{ old('parimatch_url', $tournament?->parimatch_url) }}"
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

<label class="admin-upload-label" for="bbc_standings_url">{{ __('BBC standings URL') }}</label>
<input
    type="url"
    id="bbc_standings_url"
    name="bbc_standings_url"
    class="admin-upload-input"
    value="{{ old('bbc_standings_url', $tournament?->bbc_standings_url) }}"
>

<label class="admin-upload-label" for="bbc_results_url">{{ __('BBC results URL') }}</label>
<input
    type="url"
    id="bbc_results_url"
    name="bbc_results_url"
    class="admin-upload-input"
    value="{{ old('bbc_results_url', $tournament?->bbc_results_url) }}"
>

@php
    $standingsPromrelValue = old('standings_promrel');
    if ($standingsPromrelValue === null && isset($tournament) && $tournament->standings_promrel !== []) {
        $standingsPromrelValue = json_encode(
            $tournament->standings_promrel,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }
@endphp

<label class="admin-upload-label" for="standings_promrel">{{ __('Standings promotion / relegation zones') }}</label>
<textarea
    id="standings_promrel"
    name="standings_promrel"
    class="admin-upload-textarea"
    rows="12"
    spellcheck="false"
    placeholder='{"1":{"type":"promotion","name":"Champions League","subtype":"champions-league"}}'
>{{ $standingsPromrelValue }}</textarea>
<p class="admin-upload-hint">{{ __('JSON map of table positions to zone metadata (type, name, optional subtype). Leave empty to clear.') }}</p>
