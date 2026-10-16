@props(['name', 'title', 'action', 'template', 'companies' => null, 'hint' => null])
{{-- Bulk upload: download a template, fill it in, upload it. $companies adds a Company choice. --}}
<x-modal :name="$name" :show="$errors->has('file') || $errors->has('company_id')" max-width="2xl">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
        </div>
        <div class="space-y-4 px-6 py-5">
            @if ($companies)
                <x-form-row :label="__('Company')" for="{{ $name }}_company" required :messages="$errors->get('company_id')">
                    <select id="{{ $name }}_company" name="company_id" required class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <option value="">{{ __('Select company') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->establishment_name }}</option>
                        @endforeach
                    </select>
                </x-form-row>
            @endif
            <x-form-row :label="__('Excel Template')">
                <a href="{{ $template }}" class="inline-flex items-center gap-2 rounded-md border border-brand-300 px-3 py-1.5 text-sm font-medium text-brand-700 hover:bg-brand-50">{{ __('Download Template') }}</a>
                @if ($hint)<p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>@endif
            </x-form-row>
            <x-form-row :label="__('Excel File')" for="{{ $name }}_file" required :messages="$errors->get('file')">
                <input type="file" id="{{ $name }}_file" name="file" accept=".xlsx,.csv" required
                       class="block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-500">{{ __('Acceptable formats: .xlsx, .csv. Maximum file size: 10MB.') }}</p>
            </x-form-row>
        </div>
        <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
            <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
            <x-primary-button>{{ __('Import') }}</x-primary-button>
        </div>
    </form>
</x-modal>
