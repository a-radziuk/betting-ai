<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[#eaf0ff] leading-tight">
            {{ __('Dashboard') }}
        </h2>
        <p class="mt-1 text-sm text-[#9fb0d3] font-normal">{{ __('Your BetAI account overview.') }}</p>
    </x-slot>

    <div class="pb-8">
        <section class="card card-pad">
            <p class="text-[#eaf0ff] m-0">{{ __("You're logged in!") }}</p>
            <p class="text-[#9fb0d3] text-sm mt-2 mb-0">
                <a href="{{ url('/') }}" class="text-[#5de2ff] hover:underline">{{ __('Browse upcoming events') }}</a>
            </p>
        </section>
    </div>
</x-app-layout>
