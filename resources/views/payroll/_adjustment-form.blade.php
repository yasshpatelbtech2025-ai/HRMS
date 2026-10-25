{{-- Add / edit salary adjustment. Expects: $adjustment, $modal, $employees. --}}
@php
    $editing = $adjustment->exists;
    $mine = old('_modal') === $modal;
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $input = 'control';
@endphp

<form method="POST" action="{{ $editing ? route('payroll.adjustments.update', $adjustment) : route('payroll.adjustments.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">
    <div class="border-b border-line px-6 py-4">
        <h2 class="text-lg font-semibold text-ink">{{ $editing ? __('Edit adjustment') : __('Add adjustment') }}</h2>
    </div>
    <div class="space-y-4 px-6 py-5">
        <x-form-row :label="__('Employee')" for="{{ $modal }}-employee" required :messages="$error('employee')">
            @include('payroll._employee-select', ['id' => $modal.'-employee', 'selected' => $mine ? old('employee') : $adjustment->employee?->uuid])
        </x-form-row>
        <x-form-row :label="__('Title')" for="{{ $modal }}-title" required :messages="$error('title')">
            <input id="{{ $modal }}-title" name="title" list="adjustment-titles" value="{{ $mine ? old('title') : $adjustment->title }}" required autocomplete="off"
                   placeholder="{{ __('Select title or type to create') }}" class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Amount')" for="{{ $modal }}-amount" required :messages="$error('amount')">
            <input type="number" step="0.01" min="0.01" id="{{ $modal }}-amount" name="amount" value="{{ $mine ? old('amount') : $adjustment->amount }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Salary month')" for="{{ $modal }}-month" required :messages="$error('salary_month')">
            <input type="month" id="{{ $modal }}-month" name="salary_month" value="{{ $mine ? old('salary_month') : $adjustment->salary_month?->format('Y-m') }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Adjustment type')" for="{{ $modal }}-type" required :messages="$error('type')">
            <select id="{{ $modal }}-type" name="type" required class="{{ $input }}">
                @foreach (\App\Models\SalaryAdjustment::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(($mine ? old('type') : $adjustment->type) === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </x-form-row>
    </div>
    <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
