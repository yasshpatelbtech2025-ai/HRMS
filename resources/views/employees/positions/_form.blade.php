{{--
    Add / edit position form, shown inside a modal.
    Expects: $position, $modal, $companies. The company can only be chosen
    when adding.
--}}
@php
    $editing = $position->exists;
    $mine = old('_modal') === $modal;
    $value = fn (string $field) => $mine ? old($field) : $position->{$field};
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $active = (string) (int) ($mine ? old('is_active', 1) : ($editing ? $position->is_active : 1));
@endphp

<form method="POST" action="{{ $editing ? route('employees.positions.update', $position) : route('employees.positions.store') }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editing ? __('Edit position') : __('New position') }}</h2>
    </div>

    <div class="space-y-4 px-6 py-5">
        @unless ($editing)
            <x-form-row :label="__('Company')" for="{{ $modal }}-company_id" required :messages="$error('company_id')">
                <select id="{{ $modal }}-company_id" name="company_id" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="">{{ __('Select company') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) $value('company_id') === (string) $company->id)>{{ $company->establishment_name }}</option>
                    @endforeach
                </select>
            </x-form-row>
        @endunless

        <x-form-row :label="__('Position name')" for="{{ $modal }}-name" required :messages="$error('name')">
            <x-text-input id="{{ $modal }}-name" name="name" class="block w-full" :value="$value('name')" :placeholder="__('Position name')" required autocomplete="off" />
        </x-form-row>

        <x-form-row :label="__('Position details')" for="{{ $modal }}-details" required :messages="$error('details')">
            <x-text-input id="{{ $modal }}-details" name="details" class="block w-full" :value="$value('details')" :placeholder="__('Position details')" required autocomplete="off" />
        </x-form-row>

        <x-form-row :label="__('Is active')" required :messages="$error('is_active')">
            <div class="flex gap-6 pt-2 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2"><input type="radio" name="is_active" value="1" @checked($active === '1') class="text-brand-600 focus:ring-brand-500"> {{ __('Active') }}</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="is_active" value="0" @checked($active === '0') class="text-brand-600 focus:ring-brand-500"> {{ __('Inactive') }}</label>
            </div>
        </x-form-row>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
        <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
