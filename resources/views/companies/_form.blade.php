{{--
    Add / edit company form, shown inside a modal.
    Expects: $company, $modal (modal name), $parents.
--}}
@php
    $editing = $company->exists;
    // Repopulate from old input only for the form that was submitted.
    $mine = old('_modal') === $modal;
    $value = fn (string $field) => $mine ? old($field) : $company->{$field};
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $choices = $editing ? $parents->reject(fn ($parent) => $parent->id === $company->id) : $parents;

    $addressFields = [
        'address_line_1' => 'Address Line 1',
        'address_line_2' => 'Address Line 2',
        'village' => 'Village',
    ];
    $lookupFields = [
        'state' => ['State', 'company-states'],
        'district' => ['District', 'company-districts'],
        'taluka' => ['Taluka', 'company-talukas'],
    ];
@endphp

<form method="POST" action="{{ $editing ? route('company.update', $company) : route('company.store') }}"
      x-data="{ parent: @js((string) $value('parent_company_id')) }">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editing ? __('Edit company') : __('Add company') }}</h2>
    </div>

    <div class="space-y-4 px-6 py-5">
        <div class="lg:grid lg:grid-cols-4 lg:items-start lg:gap-4">
            <label for="{{ $modal }}-establishment_name" class="block pt-2 text-sm font-medium text-gray-700">
                {{ __('Establishment Name') }} <span class="text-red-600">*</span>
            </label>
            <div class="mt-1 lg:col-span-3 lg:mt-0">
                <x-text-input id="{{ $modal }}-establishment_name" name="establishment_name" class="block w-full"
                              :value="$value('establishment_name')" :placeholder="__('Establishment Name')" required autocomplete="off" />
                <x-input-error class="mt-2" :messages="$error('establishment_name')" />
            </div>
        </div>

        <div class="lg:grid lg:grid-cols-4 lg:items-start lg:gap-4">
            <label for="{{ $modal }}-parent_company_id" class="block pt-2 text-sm font-medium text-gray-700">{{ __('Connected Company (if any)') }}</label>
            <div class="mt-1 lg:col-span-3 lg:mt-0">
                <select id="{{ $modal }}-parent_company_id" name="parent_company_id" x-model="parent"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                    <option value="">{{ __('Select company') }}</option>
                    @foreach ($choices as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->establishment_name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$error('parent_company_id')" />
            </div>
        </div>

        {{-- A connected company shares its parent's address, so these are hidden once one is chosen. --}}
        <div x-show="! parent" class="space-y-4">
            @foreach ($addressFields as $field => $label)
                <div class="lg:grid lg:grid-cols-4 lg:items-start lg:gap-4">
                    <label for="{{ $modal }}-{{ $field }}" class="block pt-2 text-sm font-medium text-gray-700">{{ __($label) }}</label>
                    <div class="mt-1 lg:col-span-3 lg:mt-0">
                        <x-text-input id="{{ $modal }}-{{ $field }}" :name="$field" class="block w-full"
                                      :value="$value($field)" :placeholder="__($label)" autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$error($field)" />
                    </div>
                </div>
            @endforeach

            @foreach ($lookupFields as $field => [$label, $list])
                <div class="lg:grid lg:grid-cols-4 lg:items-start lg:gap-4">
                    <label for="{{ $modal }}-{{ $field }}" class="block pt-2 text-sm font-medium text-gray-700">{{ __($label) }}</label>
                    <div class="mt-1 lg:col-span-3 lg:mt-0">
                        <x-text-input id="{{ $modal }}-{{ $field }}" :name="$field" :list="$list" class="block w-full"
                                      :value="$value($field)" :placeholder="__('Select :field or type a new one', ['field' => strtolower(__($label))])" autocomplete="off" />
                        <x-input-error class="mt-2" :messages="$error($field)" />
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
        <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
