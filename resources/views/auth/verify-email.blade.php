<x-guest-layout>
    <h1 class="text-2xl font-semibold text-ink">{{ __('Check your email') }}</h1>
    <p class="mt-1.5 text-sm text-ink-2">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mt-6" :status="__('A new verification link has been sent to the email address you provided during registration.')" />
    @endif

    <div class="mt-8 flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="h-10 w-full">{{ __('Resend Verification Email') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="rounded text-13 font-medium text-ink-2 underline-offset-2 hover:text-ink hover:underline">{{ __('Log Out') }}</button>
        </form>
    </div>
</x-guest-layout>
