<x-guest-layout>
    <h1 class="text-2xl font-semibold text-ink">{{ __('Sign in') }}</h1>
    <p class="mt-1.5 text-sm text-ink-2">{{ __('Use the email and password your administrator gave you.') }}</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@company.com" :aria-invalid="$errors->has('email') ? 'true' : null" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div x-data="{ reveal: false }">
            <div class="flex items-baseline justify-between">
                <x-input-label for="password" :value="__('Password')" class="!mb-0" />
                @if (Route::has('password.request'))
                    <a class="text-13 text-primary hover:underline" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                @endif
            </div>
            <div class="relative mt-2">
                <x-text-input id="password" class="pe-16" type="password" ::type="reveal ? 'text' : 'password'" name="password" required autocomplete="current-password" />
                <button type="button" @click="reveal = !reveal" :aria-pressed="reveal" class="absolute inset-y-1 end-1 rounded px-3 text-13 font-medium text-ink-2 hover:bg-sunken hover:text-ink" x-text="reveal ? @js(__('Hide')) : @js(__('Show'))">{{ __('Show') }}</button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm text-ink-2">
            <input id="remember_me" type="checkbox" class="size-4 rounded border-field text-primary focus:ring-primary/30" name="remember">
            {{ __('Keep me signed in on this computer') }}
        </label>

        <x-primary-button class="h-10 w-full">{{ __('Log in') }}</x-primary-button>
    </form>
</x-guest-layout>
