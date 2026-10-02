<x-app-layout>
    <x-slot name="header">{{ __('Edit company') }}</x-slot>

    <form method="POST" action="{{ route('company.update', $company) }}" class="max-w-3xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        @csrf
        @method('PUT')
        @include('companies._form', ['submit' => __('Save changes')])
    </form>
</x-app-layout>
