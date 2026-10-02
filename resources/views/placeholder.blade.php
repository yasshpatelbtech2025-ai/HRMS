<x-app-layout>
    <x-slot name="header">{{ $title }}</x-slot>

    <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-200">
        <h2 class="text-xl font-semibold text-gray-900">{{ $title }}</h2>
        <p class="mt-2 text-sm text-gray-500">{{ __('This section is not built yet.') }}</p>
    </div>
</x-app-layout>
