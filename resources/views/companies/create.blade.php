<x-app-layout :title="__('Add company')">
    <x-ui.page-header :title="__('Add company')" />
    @include('companies._form', ['action' => route('company.store'), 'submit' => __('Add company')])
</x-app-layout>
