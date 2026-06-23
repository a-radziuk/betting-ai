<header class="header">
    <div class="container header-inner">
        <a href="{{ url('/') }}" class="logo logo--{{ strtolower(config('app.logo')) }}">
            @include('layouts.partials.app-logo')
        </a>
        <div class="header-right">
            <span class="header-tag">{{ site_text('header.tagline', default: __('AI-Powered Football Betting Insights')) }}</span>
            @if ($faqPage)
                <a class="header-link" href="{{ route('faq') }}">{{ $faqPage->title }}</a>
            @endif
            @auth
                @if (auth()->user()->canAccessAdmin())
                    <a class="header-link" href="{{ route(\App\Support\AdminNavigation::homeRouteName(auth()->user())) }}">{{ __('Admin') }}</a>
                @endif
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
