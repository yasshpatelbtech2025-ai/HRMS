<x-guest-layout>
    <h1 class="text-xl font-semibold text-ink">{{ __('Choose a new password') }}</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-ui.field :label="__('Email')" for="email">
            <x-ui.input name="email" type="email" :value="$request->email" required autofocus autocomplete="username" />
        </x-ui.field>
        <x-ui.field :label="__('New password')" for="password" :hint="__('At least 8 characters.')">
            <x-ui.input name="password" type="password" required autocomplete="new-password" />
        </x-ui.field>
        <x-ui.field :label="__('Type it again')" for="password_confirmation">
            <x-ui.input name="password_confirmation" type="password" required autocomplete="new-password" />
        </x-ui.field>
        <x-ui.button class="w-full">{{ __('Save new password') }}</x-ui.button>
    </form>
</x-guest-layout>
