<section class="home-hero-banner" aria-labelledby="home-hero-title">
    <div class="home-hero-banner-pitch" aria-hidden="true"></div>
    <div class="home-hero-banner-inner">
        <div class="home-hero-banner-content">
            <p class="home-hero-banner-eyebrow">{{ __('AI-powered insights') }}</p>
            <h1 id="home-hero-title" class="home-hero-banner-title">{{ __('Smart football bets with AI') }}</h1>
            <p class="home-hero-banner-lead">
                {{ __('Get your best betting tips, sharper match reads, and data-driven picks for every fixture on the board.') }}
            </p>
            <div class="home-hero-banner-actions">
                <a href="#upcoming-events" class="btn btn-primary">{{ __('Browse fixtures') }}</a>
                <a href="{{ route('players.index') }}" class="btn btn-secondary">{{ __('See top players') }}</a>
            </div>
        </div>
    </div>
</section>
