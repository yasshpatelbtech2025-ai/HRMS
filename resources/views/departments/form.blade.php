@php $title = $department->exists ? $department->name : __('Add department'); @endphp
<x-app-layout :title="$title">
    <x-ui.page-header :title="$title" />

    <form method="POST" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}" data-guard
          x-data="{ companyId: @js((string) old('company_id', $department->company_id)), departments: @js($departments), self: @js($department->id) }"
          class="max-w-2xl pb-20">
        @csrf
        @if ($department->exists) @method('PUT') @endif

        <x-ui.card>
            <div class="grid gap-5">
                <x-ui.field :label="__('Company')" for="company_id" required>
                    <x-ui.combobox name="company_id" x-model="companyId" :value="$department->company_id" :placeholder="__('Search company…')"
                                   :options="$companies->map(fn ($c) => ['value' => $c->id, 'label' => $c->establishment_name])->all()" />
                </x-ui.field>
                <x-ui.field :label="__('Department name')" for="name" required>
                    <x-ui.input name="name" :value="$department->name" required :autofocus="(bool) $department->company_id" maxlength="255" />
                </x-ui.field>
                <x-ui.field :label="__('Parent department')" for="parent_id" :hint="__('Optional. Use it for sub-departments, e.g. Packing under Production.')">
                    <x-ui.combobox name="parent_id" :value="$department->parent_id" :placeholder="__('None')" :empty-label="__('None')"
                                   options-from="departments.filter(d => String(d.company_id) === String(companyId) && d.id !== self).map(d => ({ value: d.id, label: d.name }))" />
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.form-actions :cancel="route('departments.index')" :submit="$department->exists ? __('Save changes') : __('Add department')" />
    </form>
</x-app-layout>
