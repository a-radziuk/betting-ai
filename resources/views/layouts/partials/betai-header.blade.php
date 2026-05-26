<header class="header">
    <div class="container header-inner">
        <a href="{{ url('/') }}" class="logo">
            <span class="logo-badge">B</span>
            <span class="logo-text">BetAI</span>
        </a>
        <div class="header-right">
            <span class="header-tag">{{ __('AI-Powered Football Betting Insights') }}</span>
            @auth
                <a class="header-link" href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                <a class="header-link" href="{{ route('profile.edit') }}">{{ __('Profile') }}</a>
                <form method="POST" action="{{ route('logout') }}" style="display: inline; margin: 0;">
                    @csrf
                    <button type="submit" class="header-link">{{ __('Log out') }}</button>
                </form>
            @else
                <a class="header-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                <a class="header-link" href="{{ route('register') }}">{{ __('Register') }}</a>
            @endauth
        </div>
    </div>
</header>
