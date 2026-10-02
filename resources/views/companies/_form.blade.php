@php
    $fields = [
        'address_line_1' => 'Address line 1',
        'address_line_2' => 'Address line 2',
        'village' => 'Village',
        'taluka' => 'Taluka',
        'district' => 'District',
        'state' => 'State',
    ];
@endphp

<div class="space-y-5">
    <div>
        <x-input-label for="establishment_name" :value="__('Establishment name')" /> <span class="text-red-600">*</span>
        <x-text-input id="establishment_name" name="establishment_name" class="mt-1 block w-full" :value="old('establishment_name', $company->establishment_name)" required autofocus />
        <x-input-error class="mt-2" :messages="$errors->get('establishment_name')" />
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="parent_company_id" :value="__('Connected company (if any)')" />
            <select id="parent_company_id" name="parent_company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">{{ __('None') }}</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_company_id', $company->parent_company_id) == $parent->id)>{{ $parent->establishment_name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('parent_company_id')" />
        </div>
        <div>
            <x-input-label for="organization_size" :value="__('Organization size (workers)')" />
            <x-text-input id="organization_size" name="organization_size" type="number" min="0" class="mt-1 block w-full" :value="old('organization_size', $company->organization_size)" />
            <x-input-error class="mt-2" :messages="$errors->get('organization_size')" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        @foreach ($fields as $name => $label)
            <div class="{{ str_starts_with($name, 'address') ? 'sm:col-span-2' : '' }}">
                <x-input-label :for="$name" :value="__($label)" />
                <x-text-input :id="$name" :name="$name" class="mt-1 block w-full" :value="old($name, $company->$name)" />
                <x-input-error class="mt-2" :messages="$errors->get($name)" />
            </div>
        @endforeach
    </div>
</div>

<div class="mt-8 flex items-center gap-3">
    <x-primary-button>{{ $submit }}</x-primary-button>
    <a href="{{ route('company.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
</div>
