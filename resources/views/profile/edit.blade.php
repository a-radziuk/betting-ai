<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[#eaf0ff] leading-tight">
            {{ __('Profile') }}
        </h2>
        <p class="mt-1 text-sm text-[#9fb0d3] font-normal">{{ __('Manage your account, public profile, and security.') }}</p>
    </x-slot>

    <div class="profile-stack">
        <div class="card card-pad">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card card-pad">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card card-pad">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
