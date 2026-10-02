<x-app-layout>
    <x-slot name="header">{{ __('Edit :name', ['name' => $employee->full_name]) }}</x-slot>

    <form method="post" action="{{ route('employees.update', $employee) }}" class="mx-auto max-w-4xl">
        @csrf
        @method('put')

        @include('employees._form')

        <div class="mt-6 flex items-center justify-end gap-3">
            <a href="{{ route('employees.show', $employee) }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
            <x-primary-button>{{ __('Save changes') }}</x-primary-button>
        </div>
    </form>
</x-app-layout>
