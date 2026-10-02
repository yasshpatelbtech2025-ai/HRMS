<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>
    @include('employees._tabs')

    <form method="POST" action="{{ $position->exists ? route('positions.update', $position) : route('positions.store') }}" class="max-w-xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        @csrf
        @if ($position->exists) @method('PUT') @endif
        <x-input-label for="name" :value="__('Position name')" /> <span class="text-red-600">*</span>
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $position->name)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
        <div class="mt-8 flex items-center gap-3">
            <x-primary-button>{{ $position->exists ? __('Save changes') : __('Add position') }}</x-primary-button>
            <a href="{{ route('positions.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-app-layout>
