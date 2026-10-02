<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    <div class="mx-auto max-w-2xl rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">{{ __(':letter Letter', ['letter' => ucfirst($letter)]) }} — {{ $employee->full_name }}</h2>
        <p class="mt-3 text-sm text-gray-600">
            {{ __("The :letter letter template hasn't been set up yet. Share the old system's letter (saved as a page or PDF) and it will be generated here with this employee's details filled in.", ['letter' => $letter]) }}
        </p>
        <a href="{{ route('employees.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:underline">← {{ __('Back to employee list') }}</a>
    </div>
</x-app-layout>
