{{-- "New rule" modal form for a payroll rule. Expects: $rule, $modal, $companies. --}}
@php
    $editing = $rule->exists;
    $mine = old('_modal') === $modal;
    $value = fn (string $field) => $mine ? old($field) : (is_bool($rule->{$field}) ? (int) $rule->{$field} : $rule->{$field});
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $input = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $radio = 'text-indigo-600 focus:ring-indigo-500';
@endphp

<form method="POST" action="{{ $editing ? route('configs.payroll.rules.update', $rule) : route('configs.payroll.rules.store') }}"
      x-data="{ type: @js((string) ($value('type') ?? 'variable_bonus')) }">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editing ? __('Edit rule') : __('New rule') }}</h2>
    </div>

    <div class="space-y-4 px-6 py-5">
        <x-form-row :label="__('Company')" for="{{ $modal }}-company" required :messages="$error('company_id')">
            <select id="{{ $modal }}-company" name="company_id" required class="{{ $input }}">
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((string) $value('company_id') === (string) $company->id)>{{ $company->label }}</option>
                @endforeach
            </select>
        </x-form-row>
        <x-form-row :label="__('Type')" for="{{ $modal }}-type" required :messages="$error('type')">
            <select id="{{ $modal }}-type" name="type" x-model="type" required class="{{ $input }}">
                <option value="">{{ __('Select type') }}</option>
                @foreach (\App\Models\PayrollRule::TYPES as $option => $label)
                    <option value="{{ $option }}">{{ __($label) }}</option>
                @endforeach
            </select>
        </x-form-row>
        <x-form-row :label="__('Name')" for="{{ $modal }}-name" required :messages="$error('name')">
            <input id="{{ $modal }}-name" name="name" value="{{ $value('name') }}" required class="{{ $input }}">
        </x-form-row>
        <div x-show="type === 'variable_bonus'" class="space-y-4">
            <x-form-row :label="__('Amount')" for="{{ $modal }}-amount" required :messages="$error('amount')">
                <input type="number" step="0.01" min="0" id="{{ $modal }}-amount" name="amount" value="{{ $value('amount') }}" required class="{{ $input }}">
            </x-form-row>
            <x-form-row :label="__('Month')" for="{{ $modal }}-month" required :messages="$error('month')">
                <input type="month" id="{{ $modal }}-month" name="month" value="{{ $mine ? old('month') : $rule->month?->format('Y-m') }}" required class="{{ $input }}">
            </x-form-row>
            <x-form-row :label="__('Is percent')">
                <div class="flex gap-6 pt-2 text-sm text-gray-700">
                    <label class="inline-flex items-center gap-2"><input type="radio" name="is_percent" value="1" @checked((string) $value('is_percent') === '1') class="{{ $radio }}"> {{ __('Yes') }}</label>
                    <label class="inline-flex items-center gap-2"><input type="radio" name="is_percent" value="0" @checked((string) $value('is_percent') !== '1') class="{{ $radio }}"> {{ __('No') }}</label>
                </div>
            </x-form-row>
            <x-form-row :label="__('Effect on')">
                <div class="flex gap-6 pt-2 text-sm text-gray-700">
                    <label class="inline-flex items-center gap-2"><input type="radio" name="effect_on" value="basic" @checked($value('effect_on') !== 'gross') class="{{ $radio }}"> {{ __('On basic') }}</label>
                    <label class="inline-flex items-center gap-2"><input type="radio" name="effect_on" value="gross" @checked($value('effect_on') === 'gross') class="{{ $radio }}"> {{ __('On gross') }}</label>
                </div>
            </x-form-row>
        </div>
        <x-form-row :label="__('Is active')">
            <div class="flex gap-6 pt-2 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2"><input type="radio" name="is_active" value="1" @checked((string) $value('is_active') !== '0') class="{{ $radio }}"> {{ __('Active') }}</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="is_active" value="0" @checked((string) $value('is_active') === '0') class="{{ $radio }}"> {{ __('Inactive') }}</label>
            </div>
        </x-form-row>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
        <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
