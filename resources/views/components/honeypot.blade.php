@php
    $fieldName = (string) config('honeypot.field_name');
    $timestampField = (string) config('honeypot.timestamp_field');
@endphp

<input type="hidden" name="{{ $timestampField }}" value="{{ \App\Support\Honeypot::timestampToken() }}">

<div aria-hidden="true" class="absolute -left-[9999px] h-px w-px overflow-hidden">
    <label for="{{ $fieldName }}">{{ __('Leave this field empty') }}</label>
    <input
        type="text"
        name="{{ $fieldName }}"
        id="{{ $fieldName }}"
        value=""
        tabindex="-1"
        autocomplete="off"
    >
</div>
