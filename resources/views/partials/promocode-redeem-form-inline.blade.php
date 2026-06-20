<form method="post" action="{{ route('subscribe.promocode') }}" class="home-promocode-form">
    @csrf
    <label class="home-promocode-label" for="{{ $inputId }}">{{ __('Promocode') }}</label>
    <input
        type="text"
        id="{{ $inputId }}"
        name="code"
        class="admin-upload-input home-promocode-input"
        value="{{ old('code') }}"
        placeholder="{{ __('Enter promocode') }}"
        autocomplete="off"
        spellcheck="false"
        required
    >
    <button type="submit" class="btn btn-primary home-promocode-submit">{{ __('Apply') }}</button>
    @error('code')
        <p class="admin-upload-hint admin-upload-hint--error home-promocode-error">{{ $message }}</p>
    @enderror
</form>
