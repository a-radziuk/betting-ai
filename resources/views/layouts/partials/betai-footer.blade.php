<footer>
    <div class="container footer-inner">
        <span>{{ config('app.name') }}</span>
        @if (! empty($legalPages))
            <nav class="footer-legal" aria-label="{{ __('Legal') }}">
                @foreach ($legalPages as $legalPage)
                    <a href="{{ route('legal.show', $legalPage->slug) }}">{{ $legalPage->title }}</a>
                @endforeach
            </nav>
        @endif
        <span>{{ __('Smart football markets, live opportunities, better decisions.') }}</span>
        <span>{{ now()->format('Y') }}</span>
    </div>
</footer>

@feature('cookie_consent')
    <div class="container footer-cookie-settings">
        <button type="button" class="footer-cookie-settings-link" data-cookie-settings-open>
            {{ __('Cookie settings') }}
        </button>
    </div>
@endfeature

@include('layouts.partials.cookie-consent')
