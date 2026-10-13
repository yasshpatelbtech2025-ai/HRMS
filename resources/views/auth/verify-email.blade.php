<x-guest-layout>
    <h1 class="text-xl font-semibold text-ink">{{ __('Check your email') }}</h1>
    <p class="mt-1 text-sm text-ink-3">{{ __('We sent a verification link to your email address. Click it to continue. If it hasn’t arrived, we can send another.') }}</p>

    <div class="mt-8 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button>{{ __('Resend email') }}</x-ui.button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button variant="ghost">{{ __('Log out') }}</x-ui.button>
        </form>
    </div>
</x-guest-layout>
