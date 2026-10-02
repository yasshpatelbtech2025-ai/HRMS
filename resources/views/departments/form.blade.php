<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>
    @include('employees._tabs')

    <form method="POST" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}"
          x-data="{ companyId: @js((string) old('company_id', $department->company_id)), all: @js($departments), parentId: @js((string) old('parent_id', $department->parent_id)), self: @js($department->id) }"
          class="max-w-xl space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        @csrf
        @if ($department->exists) @method('PUT') @endif

        <div>
            <x-input-label for="company_id" :value="__('Company')" /> <span class="text-red-600">*</span>
            <select id="company_id" name="company_id" x-model="companyId" @change="parentId = ''" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">{{ __('Select company') }}</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->establishment_name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('company_id')" />
        </div>
        <div>
            <x-input-label for="name" :value="__('Department name')" /> <span class="text-red-600">*</span>
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $department->name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
        <div>
            <x-input-label for="parent_id" :value="__('Parent department (optional)')" />
            <select id="parent_id" name="parent_id" x-model="parentId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                <option value="">{{ __('None') }}</option>
                <template x-for="d in all.filter(d => String(d.company_id) === String(companyId) && d.id !== self)" :key="d.id">
                    <option :value="d.id" x-text="d.name" :selected="String(d.id) === String(parentId)"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('parent_id')" />
        </div>
        <div class="flex items-center gap-3 pt-3">
            <x-primary-button>{{ $department->exists ? __('Save changes') : __('Add department') }}</x-primary-button>
            <a href="{{ route('departments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-app-layout>
