<x-guest-layout>
    <h1 class="text-2xl font-semibold text-ink">{{ __('Reset your password') }}</h1>
    <p class="mt-1.5 text-sm text-ink-2">{{ __('Enter the email you sign in with. We will email you a link to choose a new password.') }}</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="name@company.com" :aria-invalid="$errors->has('email') ? 'true' : null" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <x-primary-button class="h-10 w-full">{{ __('Email Password Reset Link') }}</x-primary-button>
        <p class="text-center text-13"><a href="{{ route('login') }}" class="font-medium text-primary hover:underline">{{ __('Back to sign in') }}</a></p>
    </form>
</x-guest-layout>
