<x-guest-layout>
    <h1 class="text-xl font-semibold text-ink">{{ __('Sign in') }}</h1>
    <p class="mt-1 text-sm text-ink-3">{{ __('Use the email and password your administrator gave you.') }}</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div x-data="{ reveal: false }">
            <div class="flex items-baseline justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-13 text-primary hover:underline" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                @endif
            </div>
            <div class="relative mt-1.5">
                <x-text-input id="password" class="block w-full pe-16" type="password" ::type="reveal ? 'text' : 'password'" name="password" required autocomplete="current-password" />
                <button type="button" @click="reveal = !reveal" :aria-pressed="reveal" class="absolute inset-y-0 end-0 px-3 text-13 text-ink-3 hover:text-ink" x-text="reveal ? @js(__('Hide')) : @js(__('Show'))">{{ __('Show') }}</button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm text-ink-2">
            <input id="remember_me" type="checkbox" class="size-4 rounded border-line-strong text-primary focus:ring-primary/30" name="remember">
            {{ __('Keep me signed in on this computer') }}
        </label>

        <x-primary-button class="w-full">{{ __('Log in') }}</x-primary-button>
    </form>
</x-guest-layout>
