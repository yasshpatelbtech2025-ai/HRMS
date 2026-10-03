{{-- Add / edit leave type modal form. Expects: $leaveType, $modal, $companies. --}}
@php
    $editing = $leaveType->exists;
    $mine = old('_modal') === $modal;
    $value = fn (string $field) => $mine ? old($field) : $leaveType->{$field};
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $input = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500';
@endphp

<form method="POST" action="{{ $editing ? route('attendance.leave.types.update', $leaveType) : route('attendance.leave.types.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editing ? __('Edit leave type') : __('Add leave type') }}</h2>
    </div>

    <div class="space-y-4 px-6 py-5">
        <x-form-row :label="__('Company')" for="{{ $modal }}-company_id" required :messages="$error('company_id')">
            <select id="{{ $modal }}-company_id" name="company_id" required class="{{ $input }}">
                <option value="">{{ __('Select company') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) $value('company_id') === (string) $company->id)>{{ $company->label }}</option>
                @endforeach
            </select>
        </x-form-row>
        <x-form-row :label="__('Leave type')" for="{{ $modal }}-name" required :messages="$error('name')">
            <input id="{{ $modal }}-name" name="name" value="{{ $value('name') }}" required placeholder="{{ __('e.g. Privilege Leave') }}" class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Leave code')" for="{{ $modal }}-code" required :messages="$error('code')">
            <input id="{{ $modal }}-code" name="code" value="{{ $value('code') }}" required maxlength="20" placeholder="{{ __('e.g. PL') }}" class="{{ $input }} uppercase">
        </x-form-row>
        <x-form-row :label="__('Leave days')" for="{{ $modal }}-days" required :messages="$error('days')">
            <input type="number" step="0.5" min="0" id="{{ $modal }}-days" name="days" value="{{ $value('days') }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('Is paid')" for="{{ $modal }}-is_paid" :messages="$error('is_paid')">
            <input type="hidden" name="is_paid" value="0">
            <input type="checkbox" id="{{ $modal }}-is_paid" name="is_paid" value="1" @checked($value('is_paid')) class="mt-2.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
        </x-form-row>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
        <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
