<x-app-layout>
    <x-slot name="header">{{ __('Profile') }}</x-slot>

    <x-ui.page-header :title="__('Your profile')" :description="__('Your name, sign-in email and password. Only you can change these.')" />

    <div class="max-w-3xl space-y-6">
        <section class="card p-5 sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section class="card p-5 sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>
    </div>
</x-app-layout>
