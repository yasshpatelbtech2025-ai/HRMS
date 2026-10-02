<x-app-layout>
    <x-slot name="header">{{ __('Add company') }}</x-slot>

    <form method="POST" action="{{ route('company.store') }}" class="max-w-3xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        @csrf
        @include('companies._form', ['submit' => __('Add company')])
    </form>
</x-app-layout>
