<x-guest-layout :page-title="__('BetAI | Register')">
    <x-slot name="subtitle">{{ __('Create an account to continue with BetAI.') }}</x-slot>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-[#9fb0d3] hover:text-[#eaf0ff] rounded-md focus:outline-none focus:ring-2 focus:ring-[#5de2ff]" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-[#3b4e75]"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="bg-[#13213b] px-2 text-[#9fb0d3]">{{ __('or sign up with') }}</span>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-2">
            <a href="{{ route('social.redirect', 'google') }}" class="inline-flex justify-center rounded-md border border-[#3b4e75] px-4 py-2 text-sm font-medium text-[#dce7ff] hover:bg-[#152540]">
                {{ __('Sign up with Google') }}
            </a>
            <a href="{{ route('social.redirect', 'facebook') }}" class="inline-flex justify-center rounded-md border border-[#3b4e75] px-4 py-2 text-sm font-medium text-[#dce7ff] hover:bg-[#152540]">
                {{ __('Sign up with Facebook') }}
            </a>
            <a href="{{ route('social.redirect', 'github') }}" class="inline-flex justify-center rounded-md border border-[#3b4e75] px-4 py-2 text-sm font-medium text-[#dce7ff] hover:bg-[#152540]">
                {{ __('Sign up with GitHub') }}
            </a>
        </div>
    </div>
</x-guest-layout>
