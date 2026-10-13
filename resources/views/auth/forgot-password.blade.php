<x-guest-layout>
    <h1 class="text-xl font-semibold text-ink">{{ __('Reset your password') }}</h1>
    <p class="mt-1 text-sm text-ink-3">{{ __('Enter your email and we’ll send you a link to choose a new password.') }}</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <x-ui.field :label="__('Email')" for="email">
            <x-ui.input name="email" type="email" required autofocus autocomplete="username" />
        </x-ui.field>
        <x-ui.button class="w-full">{{ __('Email me a reset link') }}</x-ui.button>
        <p class="text-center text-13"><a href="{{ route('login') }}" class="text-ink-3 hover:text-ink">{{ __('Back to sign in') }}</a></p>
    </form>
</x-guest-layout>
