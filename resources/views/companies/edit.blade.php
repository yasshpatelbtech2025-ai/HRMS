<x-app-layout :title="$company->establishment_name">
    <x-ui.page-header :title="$company->establishment_name">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('employees.index', ['company_id' => $company->id])" icon="users">{{ __('View employees') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
    @include('companies._form', ['action' => route('company.update', $company), 'submit' => __('Save changes')])
</x-app-layout>
