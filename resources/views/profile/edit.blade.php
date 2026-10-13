<x-app-layout :title="__('Profile')">
    <x-ui.page-header :title="__('Profile')" :description="__('Your sign-in details.')" />

    <div class="max-w-4xl space-y-5">
        @include('profile.partials.update-profile-information-form')
        @include('profile.partials.update-password-form')
    </div>
</x-app-layout>
