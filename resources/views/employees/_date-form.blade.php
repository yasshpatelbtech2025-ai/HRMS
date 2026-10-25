{{--
    Modal form that sets one date on an employee (id card assign date,
    separation date). When adding, the employee is chosen from $candidates;
    when editing, $employee is fixed.
    Expects: $modal, $title, $action, $method, $field, $label, and either
    $candidates or $employee (+ optional $value).
--}}
@php
    $mine = old('_modal') === $modal;
    $error = fn (string $name) => $mine ? $errors->get($name) : [];
    $current = $mine ? old($field) : ($value ?? today()->toDateString());
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @method($method)
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
    </div>

    <div class="space-y-4 px-6 py-5">
        @isset($candidates)
            <x-form-row :label="__('Employee')" for="{{ $modal }}-employee" required :messages="$error('employee')">
                <select id="{{ $modal }}-employee" name="employee" required
                        class="control">
                    <option value="">{{ __('Select employee') }}</option>
                    @foreach ($candidates as $candidate)
                        <option value="{{ $candidate->uuid }}" @selected($mine && old('employee') === $candidate->uuid)>
                            {{ $candidate->employee_code }} - {{ $candidate->full_name }} ({{ $candidate->company?->establishment_name }})
                        </option>
                    @endforeach
                </select>
                @if ($candidates->isEmpty())
                    <p class="mt-1 text-xs text-gray-500">{{ __('Every employee already has one.') }}</p>
                @endif
            </x-form-row>
        @else
            <x-form-row :label="__('Employee')">
                <p class="pt-2 text-sm text-gray-900">{{ $employee->employee_code }} - {{ $employee->full_name }}</p>
            </x-form-row>
        @endisset

        <x-form-row :label="$label" for="{{ $modal }}-{{ $field }}" required :messages="$error($field)">
            <x-text-input type="date" id="{{ $modal }}-{{ $field }}" :name="$field" :value="$current" required class="block w-full" />
        </x-form-row>
    </div>

    <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
        <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
