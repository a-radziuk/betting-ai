<section>
    <header>
        <h2 class="text-lg font-medium text-[#eaf0ff]">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-[#9fb0d3]">
            @feature('profile_photo')
                {{ __('Update your photo, contact details, and what others see on your player page.') }}
            @else
                {{ __('Update your contact details and what others see on your player page.') }}
            @endfeature
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form
        method="post"
        action="{{ route('profile.update') }}"
        class="mt-6 space-y-6"
        @feature('profile_photo') enctype="multipart/form-data" @endfeature
    >
        @csrf
        @method('patch')

        @feature('profile_photo')
            <div>
                <x-input-label for="avatar" :value="__('Profile photo')" />
                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center">
                    @if ($url = $user->profileAvatarUrl())
                        <img src="{{ $url }}" alt="" class="h-20 w-20 shrink-0 rounded-full object-cover ring-2 ring-[#2a3550]" />
                    @else
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-[#1a2233] text-sm text-[#9fb0d3] ring-2 ring-[#2a3550]" aria-hidden="true">{{ __('None') }}</div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <input
                            id="avatar"
                            name="avatar"
                            type="file"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            class="block w-full text-sm text-[#dce7ff] file:mr-4 file:rounded-md file:border-0 file:bg-[#2a3550] file:px-3 file:py-2 file:text-sm file:font-medium file:text-[#eaf0ff] hover:file:bg-[#3a4a6a]"
                        />
                        <p class="mt-1 text-xs text-[#9fb0d3]">{{ __('JPEG, PNG, GIF or WebP, up to 2 MB.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
                    </div>
                </div>
            </div>
        @endfeature

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" autofocus autocomplete="name" placeholder="{{ __('Add your display name') }}" />
            <p class="mt-1 text-xs text-[#9fb0d3]">{{ __('Optional. Shown on your player page and in the app.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-[#dce7ff]">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-[#5de2ff] hover:text-[#8ab7ff] rounded-md focus:outline-none focus:ring-2 focus:ring-[#5de2ff]">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-[#8bffcd]">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="border-t border-[rgba(130,162,255,0.2)] pt-6">
            <h3 class="text-base font-medium text-[#eaf0ff]">{{ __('Public profile') }}</h3>
            <p class="mt-1 text-sm text-[#9fb0d3]">{{ __('Optional. Shown on your player stats page.') }}</p>

            <div class="mt-4">
                <x-input-label for="tagline" :value="__('Tagline')" />
                <x-text-input
                    id="tagline"
                    name="tagline"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('tagline', $user->tagline)"
                    maxlength="120"
                    placeholder="{{ __('e.g. Value bettor · EPL focus') }}"
                />
                <p class="mt-1 text-xs text-[#9fb0d3]">{{ __('Short line under your name (120 characters max).') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('tagline')" />
            </div>

            <div class="mt-4">
                <x-input-label for="bio" :value="__('Bio')" />
                <textarea
                    id="bio"
                    name="bio"
                    rows="4"
                    maxlength="500"
                    placeholder="{{ __('A few sentences about your approach or interests…') }}"
                    class="mt-1 block w-full rounded-md border border-[#3b4e75] bg-[#0f1b31] text-[#eaf0ff] placeholder-[#7f93bd] focus:border-[#5de2ff] focus:ring-[#5de2ff] shadow-sm"
                >{{ old('bio', $user->bio) }}</textarea>
                <p class="mt-1 text-xs text-[#9fb0d3]">{{ __('Up to 500 characters.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('bio')" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="city" :value="__('City')" />
                    <x-text-input
                        id="city"
                        name="city"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('city', $user->city)"
                        maxlength="100"
                        autocomplete="address-level2"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('city')" />
                </div>
                <div>
                    <x-input-label for="country" :value="__('Country')" />
                    <x-text-input
                        id="country"
                        name="country"
                        type="text"
                        class="mt-1 block w-full"
                        :value="old('country', $user->country)"
                        maxlength="100"
                        autocomplete="country-name"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('country')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[#9fb0d3]"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
