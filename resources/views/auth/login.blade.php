<x-guest-layout :page-title="__('BetAI | Login')">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-[#3b4e75] bg-[#0f1b31] text-[#5de2ff] shadow-sm focus:ring-[#5de2ff]" name="remember">
                <span class="ms-2 text-sm text-[#9fb0d3]">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                @if (Route::has('register'))
                    <a class="underline text-sm text-[#9fb0d3] hover:text-[#eaf0ff] rounded-md focus:outline-none focus:ring-2 focus:ring-[#5de2ff]" href="{{ route('register') }}">
                        {{ __("Don't have an account?") }}
                    </a>
                @endif
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-[#9fb0d3] hover:text-[#eaf0ff] rounded-md focus:outline-none focus:ring-2 focus:ring-[#5de2ff]" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    @if (feature('login_google') || feature('login_facebook') || feature('login_github'))
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[#3b4e75]"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-[#13213b] px-2 text-[#9fb0d3]">{{ __('or continue with') }}</span>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-2">
                @feature('login_google')
                    <a href="{{ route('social.redirect', 'google') }}" class="inline-flex justify-center rounded-md border border-[#3b4e75] px-4 py-2 text-sm font-medium text-[#dce7ff] hover:bg-[#152540]">
                        {{ __('Continue with Google') }}
                    </a>
                @endfeature
                @feature('login_facebook')
                    <a href="{{ route('social.redirect', 'facebook') }}" class="inline-flex justify-center rounded-md border border-[#3b4e75] px-4 py-2 text-sm font-medium text-[#dce7ff] hover:bg-[#152540]">
                        {{ __('Continue with Facebook') }}
                    </a>
                @endfeature
                @feature('login_github')
                    <a href="{{ route('social.redirect', 'github') }}" class="inline-flex justify-center rounded-md border border-[#3b4e75] px-4 py-2 text-sm font-medium text-[#dce7ff] hover:bg-[#152540]">
                        {{ __('Continue with GitHub') }}
                    </a>
                @endfeature
            </div>
        </div>
    @endif
</x-guest-layout>
