@php
    $subscription = $subscription ?? null;
@endphp

<label class="admin-upload-label" for="user_id">{{ __('User ID') }}</label>
<input
    type="number"
    id="user_id"
    name="user_id"
    class="admin-upload-input"
    value="{{ old('user_id', $subscription?->user_id) }}"
    min="1"
    step="1"
    inputmode="numeric"
    required
>

<label class="admin-upload-label" for="prediction_type">{{ __('Prediction type') }}</label>
<input
    type="text"
    id="prediction_type"
    name="prediction_type"
    class="admin-upload-input"
    value="{{ old('prediction_type', $subscription?->prediction_type) }}"
    maxlength="80"
    required
>
