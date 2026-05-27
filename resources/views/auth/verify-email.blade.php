<x-guest-layout :page-title="__('BetAI | Verify email')">
    <x-slot name="subtitle">{{ __('Confirm your email address to continue.') }}</x-slot>
    <div class="mb-4 text-sm text-[#9fb0d3]">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-[#8bffcd]">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-[#9fb0d3] hover:text-[#eaf0ff] rounded-md focus:outline-none focus:ring-2 focus:ring-[#5de2ff]">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
