<section class="home-hero-banner" aria-labelledby="home-hero-title">
    <div class="home-hero-banner-pitch" aria-hidden="true"></div>
    <div class="home-hero-banner-inner">
        <div class="home-hero-banner-content">
            <p class="home-hero-banner-eyebrow">{{ site_text('home.hero.eyebrow', default: __('AI-powered insights')) }}</p>
            <h1 id="home-hero-title" class="home-hero-banner-title">{{ site_text('home.hero.title', default: __('Smart football bets with AI')) }}</h1>
            <p class="home-hero-banner-lead">
                {{ site_text('home.hero.lead', default: __('Get your best betting tips, sharper match reads, and data-driven picks for every fixture on the board.')) }}
            </p>
            <div class="home-hero-banner-actions">
                <a href="#upcoming-events" class="btn btn-primary">{{ __('Browse fixtures') }}</a>
                <a href="{{ route('players.index') }}" class="btn btn-secondary">{{ __('See top players') }}</a>
            </div>
        </div>

        @if ($heroTopUserMetric ?? null)
            @php
                $heroUser = $heroTopUserMetric->user;
                $heroCurrency = $heroUser->wallet?->currency ?? 'EUR';
                $heroAvatarUrl = $heroUser->profileAvatarUrl();
                $heroNameTrim = trim((string) $heroUser->name);
                $heroInitial = mb_strtoupper(mb_substr($heroNameTrim !== '' ? $heroNameTrim : '?', 0, 1));
            @endphp
            <aside class="home-hero-banner-featured" aria-label="{{ __('Top performer') }}">
                <p class="home-hero-banner-featured-label">{{ __('Top performer') }}</p>
                <a
                    href="{{ route('players.show', $heroUser) }}"
                    class="home-hero-banner-featured-card"
                    aria-label="{{ __('View :name player page', ['name' => $heroUser->name]) }}"
                >
                    <div class="home-hero-banner-featured-avatar">
                        @if ($heroAvatarUrl)
                            <img
                                src="{{ $heroAvatarUrl }}"
                                alt=""
                                class="home-hero-banner-featured-avatar-img"
                                loading="lazy"
                                decoding="async"
                            />
                        @else
                            <span class="home-hero-banner-featured-avatar-placeholder" aria-hidden="true">{{ $heroInitial }}</span>
                        @endif
                    </div>
                    <div class="home-hero-banner-featured-body">
                        <p class="home-hero-banner-featured-name">{{ $heroUser->name }}</p>
                        <p class="home-hero-banner-featured-type">{{ $heroTopUserMetric->typeLabel() }}</p>
                        <p class="home-hero-banner-featured-amount">
                            +{{ number_format((float) $heroTopUserMetric->amount, 2) }} {{ $heroCurrency }}
                        </p>
                    </div>
                </a>
            </aside>
        @endif
    </div>
</section>
