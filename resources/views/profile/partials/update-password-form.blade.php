<x-ui.card>
    <div class="grid gap-x-8 gap-y-5 lg:grid-cols-[14rem_1fr]">
        <div>
            <h2 class="text-sm font-semibold text-ink">{{ __('Password') }}</h2>
            <p class="mt-1 text-13 text-ink-3">{{ __('Use at least 8 characters. A short sentence is easier to remember.') }}</p>
        </div>
        <form method="post" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            @method('put')
            <x-ui.field :label="__('Current password')" for="update_password_current_password" error="current_password" bag="updatePassword">
                <x-ui.input name="current_password" id="update_password_current_password" type="password" autocomplete="current-password" bag="updatePassword" />
            </x-ui.field>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.field :label="__('New password')" for="update_password_password" error="password" bag="updatePassword">
                    <x-ui.input name="password" id="update_password_password" type="password" autocomplete="new-password" bag="updatePassword" />
                </x-ui.field>
                <x-ui.field :label="__('Type it again')" for="update_password_password_confirmation" error="password_confirmation" bag="updatePassword">
                    <x-ui.input name="password_confirmation" id="update_password_password_confirmation" type="password" autocomplete="new-password" bag="updatePassword" />
                </x-ui.field>
            </div>
            <x-ui.button>{{ __('Change password') }}</x-ui.button>
        </form>
    </div>
</x-ui.card>
