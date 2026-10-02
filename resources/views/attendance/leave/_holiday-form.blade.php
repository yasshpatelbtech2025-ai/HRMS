{{-- Add / edit holiday (master list) modal form. Expects: $holiday, $modal. --}}
@php
    $editing = $holiday->exists;
    $mine = old('_modal') === $modal;
    $value = fn (string $field) => $mine ? old($field) : ($holiday->{$field} instanceof \DateTimeInterface ? $holiday->{$field}->toDateString() : $holiday->{$field});
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $input = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<form method="POST" action="{{ $editing ? route('attendance.leave.holiday-list.update', $holiday) : route('attendance.leave.holiday-list.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editing ? __('Edit holiday') : __('New holiday') }}</h2>
    </div>

    <div class="space-y-4 px-6 py-5">
        <x-form-row :label="__('Holiday name')" for="{{ $modal }}-name" required :messages="$error('name')">
            <input id="{{ $modal }}-name" name="name" value="{{ $value('name') }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('From date')" for="{{ $modal }}-start" required :messages="$error('start_date')">
            <input type="date" id="{{ $modal }}-start" name="start_date" value="{{ $value('start_date') }}" required class="{{ $input }}">
        </x-form-row>
        <x-form-row :label="__('To date')" for="{{ $modal }}-end" required :messages="$error('end_date')">
            <input type="date" id="{{ $modal }}-end" name="end_date" value="{{ $value('end_date') }}" required class="{{ $input }}">
        </x-form-row>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
        <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
