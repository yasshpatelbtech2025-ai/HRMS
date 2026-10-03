{{-- Add / edit salary advance. Expects: $advance, $modal, $employees. --}}
@php
    $editing = $advance->exists;
    $mine = old('_modal') === $modal;
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $input = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500';
    $active = (string) ($mine ? old('is_active') : (int) $advance->is_active);
@endphp

<form method="POST" action="{{ $editing ? route('payroll.advances.update', $advance) : route('payroll.advances.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editing ? __('Edit salary advance') : __('Add salary advance') }}</h2>
    </div>
    <div class="space-y-4 px-6 py-5">
        <x-form-row :label="__('Employee')" for="{{ $modal }}-employee" required :messages="$error('employee')">
            @include('payroll._employee-select', ['id' => $modal.'-employee', 'selected' => $mine ? old('employee') : $advance->employee?->uuid])
        </x-form-row>
        <x-form-row :label="__('Amount')" for="{{ $modal }}-amount" required :messages="$error('amount')">
            <input type="number" step="0.01" min="1" id="{{ $modal }}-amount" name="amount" value="{{ $mine ? old('amount') : $advance->amount }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Salary month')" for="{{ $modal }}-month" required :messages="$error('salary_month')">
            <input type="month" id="{{ $modal }}-month" name="salary_month" value="{{ $mine ? old('salary_month') : $advance->salary_month?->format('Y-m') }}" required class="{{ $input }}">
            <p class="mt-1 text-xs text-gray-500">{{ __('The month whose salary it is recovered from.') }}</p>
        </x-form-row>
        <x-form-row :label="__('Advance date')" for="{{ $modal }}-date" required :messages="$error('advance_date')">
            <input type="date" id="{{ $modal }}-date" name="advance_date" value="{{ $mine ? old('advance_date') : $advance->advance_date?->toDateString() }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Is active')" required>
            <div class="flex gap-6 pt-2 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2"><input type="radio" name="is_active" value="1" @checked($active !== '0') class="text-brand-600 focus:ring-brand-500"> {{ __('Active') }}</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="is_active" value="0" @checked($active === '0') class="text-brand-600 focus:ring-brand-500"> {{ __('Inactive') }}</label>
            </div>
        </x-form-row>
        <x-form-row :label="__('Attachment')" for="{{ $modal }}-attachment" :messages="$error('attachment')">
            <input type="file" id="{{ $modal }}-attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium">
        </x-form-row>
    </div>
    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
        <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
