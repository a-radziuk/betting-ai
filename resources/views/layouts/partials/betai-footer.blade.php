<footer>
    <div class="container footer-inner">
        <span>{{ config('app.name') }}</span>
        <nav class="footer-legal" aria-label="{{ __('Site links') }}">
            <a href="{{ route('blog.index') }}">{{ __('Blog') }}</a>
            @if ($faqPage || ! empty($legalPages))
                @if ($faqPage)
                    <a href="{{ route('faq') }}">{{ $faqPage->title }}</a>
                @endif
                @foreach ($legalPages as $legalPage)
                    <a href="{{ route('legal.show', $legalPage->slug) }}">{{ $legalPage->title }}</a>
                @endforeach
            @endif
        </nav>
        <span>{{ site_text('footer.tagline', default: __('Smart football markets, live opportunities, better decisions.')) }}</span>
        <span>{{ now()->format('Y') }}</span>
    </div>
</footer>

@unless ($skipCookieConsent ?? false)
@feature('cookie_consent')
    <div class="container footer-cookie-settings">
        <button type="button" class="footer-cookie-settings-link" data-cookie-settings-open>
            {{ __('Cookie settings') }}
        </button>
    </div>
@endfeature

@include('layouts.partials.cookie-consent')
@endunless
