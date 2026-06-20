<form method="post" action="{{ route('subscribe.promocode') }}" class="admin-upload-form">
    @csrf
    <label class="admin-upload-label" for="{{ $inputId }}">{{ __('Promocode') }}</label>
    <input
        type="text"
        id="{{ $inputId }}"
        name="code"
        class="admin-upload-input"
        value="{{ old('code') }}"
        autocomplete="off"
        spellcheck="false"
        required
    >
    @error('code')
        <p class="admin-upload-hint admin-upload-hint--error">{{ $message }}</p>
    @enderror
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary admin-upload-submit">{{ __('Apply promocode') }}</button>
    </div>
</form>
