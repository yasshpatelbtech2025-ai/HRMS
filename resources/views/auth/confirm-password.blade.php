<x-guest-layout>
    <h1 class="text-xl font-semibold text-ink">{{ __('Confirm your password') }}</h1>
    <p class="mt-1 text-sm text-ink-3">{{ __('This is a protected area. Please enter your password to continue.') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf
        <x-ui.field :label="__('Password')" for="password">
            <x-ui.input name="password" type="password" required autofocus autocomplete="current-password" />
        </x-ui.field>
        <x-ui.button class="w-full">{{ __('Confirm') }}</x-ui.button>
    </form>
</x-guest-layout>
