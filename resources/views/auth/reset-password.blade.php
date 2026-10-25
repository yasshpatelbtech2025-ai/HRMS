<x-guest-layout>
    <h1 class="text-2xl font-semibold text-ink">{{ __('Choose a new password') }}</h1>
    <p class="mt-1.5 text-sm text-ink-2">{{ __('Use at least 8 characters. You will sign in with it from now on.') }}</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" :aria-invalid="$errors->has('email') ? 'true' : null" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" :aria-invalid="$errors->has('password') ? 'true' : null" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Type it again')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="h-10 w-full">{{ __('Reset Password') }}</x-primary-button>
    </form>
</x-guest-layout>
