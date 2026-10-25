{{--
    Add / edit sub department form, shown inside a modal.
    Expects: $subDepartment, $modal, $companies, $departments.

    Department accepts an existing name or a new one. Picking a department
    that belongs to exactly one company fixes the company to match, as the
    old system did.
--}}
@php
    $editing = $subDepartment->exists;
    $mine = old('_modal') === $modal;
    $error = fn (string $field) => $mine ? $errors->get($field) : [];
    $state = [
        'department' => $mine ? old('department') : $subDepartment->department?->name,
        'company' => (string) ($mine ? old('company_id') : $subDepartment->company_id),
        'active' => (string) (int) ($mine ? old('is_active', 1) : ($subDepartment->exists ? $subDepartment->is_active : 1)),
    ];
@endphp

<form method="POST" action="{{ $editing ? route('employees.departments.update', $subDepartment) : route('employees.departments.store') }}"
      x-data="{
          ...@js($state),
          departments: @js($departments),
          get lockedCompany() {
              const name = (this.department || '').trim().toLowerCase();
              const companies = [...new Set(this.departments.filter(d => d.name.toLowerCase() === name).map(d => String(d.company_id)))];
              return companies.length === 1 ? companies[0] : null;
          },
      }"
      x-effect="if (lockedCompany) company = lockedCompany">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="_modal" value="{{ $modal }}">

    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ $editing ? __('Edit sub department') : __('New sub department') }}</h2>
    </div>

    <div class="space-y-4 px-6 py-5">
        <x-form-row :label="__('Department')" for="{{ $modal }}-department" required :messages="$error('department')">
            <x-text-input id="{{ $modal }}-department" name="department" list="sub-department-departments" x-model="department"
                          class="block w-full" :placeholder="__('Select department or type a new one')" required autocomplete="off" />
        </x-form-row>

        <x-form-row :label="__('Company')" for="{{ $modal }}-company_id" required :messages="$error('company_id')">
            {{-- A disabled select isn't submitted, so the locked company is sent from a hidden field instead. --}}
            <template x-if="lockedCompany"><input type="hidden" name="company_id" :value="company"></template>
            <select id="{{ $modal }}-company_id" :name="lockedCompany ? null : 'company_id'" x-model="company" :disabled="!! lockedCompany" required
                    class="control disabled:bg-gray-100 disabled:text-gray-600">
                <option value="">{{ __('Select company') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->label }}</option>
                @endforeach
            </select>
        </x-form-row>

        <x-form-row :label="__('Sub department name')" for="{{ $modal }}-name" required :messages="$error('name')">
            <x-text-input id="{{ $modal }}-name" name="name" class="block w-full" :value="$mine ? old('name') : $subDepartment->name"
                          :placeholder="__('Sub department name')" required autocomplete="off" />
        </x-form-row>

        <x-form-row :label="__('Is active')" required :messages="$error('is_active')">
            <div class="flex gap-6 pt-2 text-sm text-gray-700">
                <label class="inline-flex items-center gap-2"><input type="radio" name="is_active" value="1" x-model="active" class="text-brand-600 focus:ring-brand-500"> {{ __('Active') }}</label>
                <label class="inline-flex items-center gap-2"><input type="radio" name="is_active" value="0" x-model="active" class="text-brand-600 focus:ring-brand-500"> {{ __('Inactive') }}</label>
            </div>
        </x-form-row>
    </div>

    <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
        <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
        <x-primary-button>{{ __('Save') }}</x-primary-button>
    </div>
</form>
