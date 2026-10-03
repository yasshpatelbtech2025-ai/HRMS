<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Sub department list') }}</h2>
            <x-add-button modal="create-sub-department">{{ __('Add sub department') }}</x-add-button>
        </div>

        @php($exportQuery = request()->only('search', 'sort', 'direction'))
        <x-list-toolbar :action="route('employees.departments')" :per-page="$perPage" :exports="[
            __('CSV') => route('employees.departments.export', ['format' => 'csv'] + $exportQuery),
            __('Excel') => route('employees.departments.export', ['format' => 'xlsx'] + $exportQuery),
        ]" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="w-16 px-5 py-3">{{ __('Sl') }}</th>
                        <x-sort-header column="company" default>{{ __('Company name') }}</x-sort-header>
                        <x-sort-header column="name">{{ __('Sub department name') }}</x-sort-header>
                        <x-sort-header column="department">{{ __('Department name') }}</x-sort-header>
                        <x-sort-header column="status">{{ __('Status') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($subDepartments as $subDepartment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $subDepartments->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3">{{ $subDepartment->company->establishment_name }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $subDepartment->name }}</td>
                            <td class="px-5 py-3">{{ $subDepartment->department->name }}</td>
                            <td class="px-5 py-3"><x-status-badge :active="$subDepartment->is_active" /></td>
                            <td class="px-5 py-3">
                                <x-row-actions :edit-modal="'edit-sub-department-'.$subDepartment->uuid"
                                               :delete-url="route('employees.departments.destroy', $subDepartment)"
                                               :delete-confirm="__('Delete sub department :name?', ['name' => $subDepartment->name])" />

                                <x-modal name="edit-sub-department-{{ $subDepartment->uuid }}" :show="old('_modal') === 'edit-sub-department-'.$subDepartment->uuid" max-width="2xl">
                                    @include('employees.departments._form', ['modal' => 'edit-sub-department-'.$subDepartment->uuid])
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        @if (request('search'))
                            <x-empty-state colspan="6" icon="search" :title="__('No sub departments match your search.')">{{ __('Try a different name.') }}</x-empty-state>
                        @else
                            <x-empty-state colspan="6" icon="building" :title="__('No sub departments yet')">
                                {{ __('Sub departments group employees within a department.') }}
                                <x-slot name="action"><x-add-button modal="create-sub-department">{{ __('Add sub department') }}</x-add-button></x-slot>
                            </x-empty-state>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$subDepartments" />
    </div>

    <x-modal name="create-sub-department" :show="old('_modal') === 'create-sub-department'" max-width="2xl">
        @include('employees.departments._form', ['subDepartment' => new \App\Models\SubDepartment, 'modal' => 'create-sub-department'])
    </x-modal>

    <datalist id="sub-department-departments">
        @foreach ($departments->unique(fn ($department) => mb_strtolower($department->name)) as $department)
            <option value="{{ $department->name }}"></option>
        @endforeach
    </datalist>
</x-app-layout>
