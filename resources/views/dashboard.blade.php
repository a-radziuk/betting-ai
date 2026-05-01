<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[#eaf0ff] leading-tight">
            {{ __('Dashboard') }}
        </h2>
        <p class="mt-1 text-sm text-[#9fb0d3] font-normal">{{ __('Your BetAI account overview.') }}</p>
    </x-slot>

    <div class="pb-8 space-y-4">
        <section class="card card-pad">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-[#c7d7fa] m-0">{{ __('Wallet') }}</h3>
            <div class="mt-3 flex flex-wrap items-baseline gap-3">
                <span class="text-3xl font-bold text-[#eaf0ff] tabular-nums">
                    {{ number_format((float) $wallet->balance, 2) }}
                </span>
                <span class="text-lg text-[#9fb0d3]">{{ $wallet->currency }}</span>
            </div>
            <p class="text-[#9fb0d3] text-sm mt-2 mb-0">
                {{ __('Available balance for betting.') }}
            </p>
        </section>

        <section class="card card-pad">
            <p class="text-[#eaf0ff] m-0">{{ __("You're logged in!") }}</p>
            <p class="text-[#9fb0d3] text-sm mt-2 mb-0">
                <a href="{{ url('/') }}" class="text-[#5de2ff] hover:underline">{{ __('Browse upcoming events') }}</a>
            </p>
        </section>
    </div>
</x-app-layout>
