<section>
    <header>
        <h2 class="text-lg font-medium text-[#eaf0ff]">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-[#9fb0d3]">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

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

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
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
