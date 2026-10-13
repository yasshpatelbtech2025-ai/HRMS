<x-app-layout :title="$title">
    <x-ui.page-header :title="$title" />

    <x-ui.card>
        <x-ui.empty-state icon="clock" :title="__(':section is coming soon', ['section' => $title])"
                          :message="__('This module hasn’t been built yet. When it is, it will appear here with the same layout as the rest of the app.')">
            <x-ui.button variant="secondary" :href="route('dashboard')">{{ __('Back to dashboard') }}</x-ui.button>
        </x-ui.empty-state>
    </x-ui.card>
</x-app-layout>
