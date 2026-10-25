<x-guest-layout>
    <h1 class="text-2xl font-semibold text-ink">{{ __('Confirm your password') }}</h1>
    <p class="mt-1.5 text-sm text-ink-2">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autofocus autocomplete="current-password" :aria-invalid="$errors->has('password') ? 'true' : null" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <x-primary-button class="h-10 w-full">{{ __('Confirm') }}</x-primary-button>
    </form>
</x-guest-layout>
