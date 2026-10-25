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

    // In address order; fields with a list suggest values but accept new ones.
    $addressFields = [
        'address_line_1' => ['Address Line 1', null],
        'address_line_2' => ['Address Line 2', null],
        'city' => ['City', 'company-cities'],
        'village' => ['Village', null],
        'taluka' => ['Taluka', 'company-talukas'],
        'district' => ['District', 'company-districts'],
        'state' => ['State', 'company-states'],
        'country' => ['Country', 'company-countries'],
    ];
    // New companies start in India.
    $addressValue = fn (string $field) => $field === 'country' && ! $editing && ! $mine ? 'India' : $value($field);
@endphp

<form method="POST" action="{{ $editing ? route('company.update', $company) : route('company.store') }}"
      x-data="{ parent: @js((string) $value('parent_company_id')) }">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-line px-6 py-4">
        <h2 class="text-lg font-semibold text-ink">{{ $editing ? __('Edit company') : __('Add company') }}</h2>
    </div>

    <div class="space-y-6 px-6 py-5">
        <div class="space-y-5">
            <x-form-row :label="__('Establishment Name')" for="{{ $modal }}-establishment_name" required :messages="$error('establishment_name')"
                        :help="__('The registered name, as it should appear on registers and invoices.')">
                <x-text-input id="{{ $modal }}-establishment_name" name="establishment_name"
                              :value="$value('establishment_name')" :placeholder="__('e.g. Shree Ganesh Fabricators')" required autocomplete="off" />
            </x-form-row>

            <x-form-row :label="__('Connected Company (if any)')" for="{{ $modal }}-parent_company_id" :messages="$error('parent_company_id')"
                        :help="__('Pick one if this is a branch or sister unit. It then uses that company\'s address.')">
                <select id="{{ $modal }}-parent_company_id" name="parent_company_id" x-model="parent" class="control">
                    <option value="">{{ __('Select company') }}</option>
                    @foreach ($choices as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->establishment_name }}</option>
                    @endforeach
                </select>
            </x-form-row>
        </div>

        {{-- A connected company shares its parent's address, so these are hidden once one is chosen. --}}
        <fieldset x-show="! parent" class="border-t border-line pt-5">
            <legend class="sr-only">{{ __('Address') }}</legend>
            <p class="mb-4 text-sm font-semibold text-ink" aria-hidden="true">{{ __('Address') }}</p>
            <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
                @foreach ($addressFields as $field => [$label, $list])
                    <x-form-row :label="__($label)" for="{{ $modal }}-{{ $field }}" :messages="$error($field)" :class="str_starts_with($field, 'address_line') ? 'sm:col-span-2' : ''">
                        @if ($list)
                            <x-text-input id="{{ $modal }}-{{ $field }}" :name="$field" :list="$list"
                                          :value="$addressValue($field)" :placeholder="__('Select :field or type a new one', ['field' => strtolower(__($label))])" autocomplete="off" />
                        @else
                            <x-text-input id="{{ $modal }}-{{ $field }}" :name="$field"
                                          :value="$addressValue($field)" autocomplete="off" />
                        @endif
                    </x-form-row>
                @endforeach
            </div>
        </fieldset>
    </div>

    <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
        <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
