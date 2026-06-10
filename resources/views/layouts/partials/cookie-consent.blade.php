@feature('cookie_consent')
    @php
        $cookieCategories = collect(config('cookie_consent.categories', []))
            ->map(fn (array $category, string $key): array => [
                'key' => $key,
                'label' => __($category['label']),
                'description' => __($category['description']),
                'required' => (bool) ($category['required'] ?? false),
            ])
            ->values()
            ->all();

        $cookiePolicyUrl = collect($legalPages ?? [])->firstWhere('slug', 'cookie-policy')
            ? route('legal.show', 'cookie-policy')
            : null;

        $cookieConsentConfig = [
            'version' => (string) config('cookie_consent.version', '1'),
            'cookieName' => (string) config('cookie_consent.cookie_name', 'cookie_consent'),
            'cookieLifetimeDays' => (int) config('cookie_consent.cookie_lifetime_days', 365),
            'storeUrl' => route('cookie-consent.store'),
            'categories' => $cookieCategories,
            'scripts' => config('cookie_consent.scripts', []),
        ];
    @endphp

    <div id="cookie-consent-banner" class="cookie-consent-banner" role="dialog" aria-modal="true" aria-labelledby="cookie-consent-title" hidden>
        <div class="cookie-consent-banner-inner card">
            <h2 id="cookie-consent-title" class="cookie-consent-title">{{ __('We use cookies') }}</h2>
            <p class="cookie-consent-text">
                {{ __('We use essential cookies to run the site. With your permission, we also use optional cookies for analytics and marketing.') }}
                @if ($cookiePolicyUrl)
                    <a href="{{ $cookiePolicyUrl }}" class="cookie-consent-link">{{ __('Read our Cookie Policy') }}</a>
                @endif
            </p>
            <div class="cookie-consent-actions">
                <button type="button" class="btn btn-secondary" data-cookie-consent-action="reject">{{ __('Reject') }}</button>
                <button type="button" class="btn btn-secondary" data-cookie-consent-action="customize">{{ __('Customize') }}</button>
                <button type="button" class="btn btn-primary" data-cookie-consent-action="accept">{{ __('Accept all') }}</button>
            </div>
        </div>
    </div>

    <div id="cookie-consent-modal" class="cookie-consent-modal" role="dialog" aria-modal="true" aria-labelledby="cookie-consent-modal-title" hidden>
        <div class="cookie-consent-modal-backdrop" data-cookie-consent-action="close-modal"></div>
        <div class="cookie-consent-modal-panel card card-pad">
            <h2 id="cookie-consent-modal-title" class="cookie-consent-title">{{ __('Cookie preferences') }}</h2>
            <p class="cookie-consent-text">{{ __('Choose whether to accept all cookies or customize optional categories. Essential cookies are always active.') }}</p>

            <fieldset class="cookie-consent-mode">
                <legend class="cookie-consent-mode-legend">{{ __('Consent choice') }}</legend>
                <label class="cookie-consent-mode-option">
                    <input
                        type="radio"
                        name="cookie-consent-mode"
                        value="accept-all"
                        data-cookie-consent-mode
                        checked
                    >
                    <span>{{ __('Accept all cookies') }}</span>
                </label>
                <label class="cookie-consent-mode-option">
                    <input
                        type="radio"
                        name="cookie-consent-mode"
                        value="customize"
                        data-cookie-consent-mode
                    >
                    <span>{{ __('Customize selection') }}</span>
                </label>
            </fieldset>

            <div class="cookie-consent-category-list" data-cookie-categories-panel>
                @foreach ($cookieCategories as $category)
                    <label class="cookie-consent-category">
                        <input
                            type="checkbox"
                            name="cookie-category-{{ $category['key'] }}"
                            value="1"
                            data-cookie-category="{{ $category['key'] }}"
                            @checked($category['required'])
                            @disabled($category['required'])
                        >
                        <span class="cookie-consent-category-copy">
                            <span class="cookie-consent-category-label">{{ $category['label'] }}</span>
                            <span class="cookie-consent-category-description">{{ $category['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="cookie-consent-actions">
                <button type="button" class="btn btn-secondary" data-cookie-consent-action="reject">{{ __('Reject all') }}</button>
                <button type="button" class="btn btn-primary" data-cookie-consent-action="accept" data-cookie-consent-accept-all>{{ __('Accept all') }}</button>
                <button type="button" class="btn btn-primary" data-cookie-consent-action="save" data-cookie-consent-save hidden>{{ __('Save preferences') }}</button>
            </div>
        </div>
    </div>

    <script>
        window.cookieConsentConfig = @json($cookieConsentConfig);
    </script>
    @vite(['resources/js/cookie-consent.js'])
@endfeature
