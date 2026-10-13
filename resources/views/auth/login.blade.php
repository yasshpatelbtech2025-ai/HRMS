<x-guest-layout>
    <h1 class="text-xl font-semibold text-ink">{{ __('Sign in') }}</h1>
    <p class="mt-1 text-sm text-ink-3">{{ __('Use the email and password your administrator gave you.') }}</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf
        <x-ui.field :label="__('Email')" for="email">
            <x-ui.input name="email" type="email" required autofocus autocomplete="username" />
        </x-ui.field>

        <x-ui.field for="password" x-data="{ reveal: false }">
            <div class="flex items-baseline justify-between">
                <label for="password" class="text-13 font-medium text-ink-2">{{ __('Password') }}</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-13 text-primary hover:underline">{{ __('Forgot password?') }}</a>
                @endif
            </div>
            <div class="relative">
                <input id="password" name="password" required autocomplete="current-password" :type="reveal ? 'text' : 'password'" type="password" class="control pe-16" @error('password') aria-invalid="true" @enderror>
                <button type="button" @click="reveal = !reveal" class="absolute inset-y-0 end-0 px-3 text-13 text-ink-3 hover:text-ink" x-text="reveal ? '{{ __('Hide') }}' : '{{ __('Show') }}'" :aria-pressed="reveal">{{ __('Show') }}</button>
            </div>
        </x-ui.field>

        <label class="flex items-center gap-2 text-sm text-ink-2">
            <x-ui.checkbox id="remember_me" name="remember" />{{ __('Keep me signed in on this computer') }}
        </label>

        <x-ui.button class="w-full">{{ __('Sign in') }}</x-ui.button>
    </form>
</x-guest-layout>
