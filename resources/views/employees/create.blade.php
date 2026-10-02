<x-app-layout>
    <x-slot name="header">{{ __('Add employee') }}</x-slot>

    <form method="post" action="{{ route('employees.store') }}" class="mx-auto max-w-4xl">
        @csrf

        @include('employees._form')

        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('employees.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
            <x-primary-button>{{ __('Save employee') }}</x-primary-button>
        </div>
    </form>
</x-app-layout>
