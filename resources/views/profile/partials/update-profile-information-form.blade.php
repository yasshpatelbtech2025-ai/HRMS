<x-ui.card>
    <div class="grid gap-x-8 gap-y-5 lg:grid-cols-[14rem_1fr]">
        <div>
            <h2 class="text-sm font-semibold text-ink">{{ __('Your details') }}</h2>
            <p class="mt-1 text-13 text-ink-3">{{ __('Your name appears on records you create.') }}</p>
        </div>
        <div>
            <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

            <form method="post" action="{{ route('profile.update') }}" class="space-y-5" data-guard>
                @csrf
                @method('patch')
                <x-ui.field :label="__('Name')" for="name" required>
                    <x-ui.input name="name" :value="$user->name" required autocomplete="name" />
                </x-ui.field>
                <x-ui.field :label="__('Email')" for="email" required>
                    <x-ui.input name="email" type="email" :value="$user->email" required autocomplete="username" />
                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <p class="text-13 text-warn">
                            {{ __('Your email address is not verified.') }}
                            <button form="send-verification" class="font-medium underline">{{ __('Send the link again') }}</button>
                        </p>
                    @endif
                </x-ui.field>
                <x-ui.button>{{ __('Save') }}</x-ui.button>
            </form>
        </div>
    </div>
</x-ui.card>
