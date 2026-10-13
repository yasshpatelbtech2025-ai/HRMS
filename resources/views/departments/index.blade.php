<x-app-layout :title="__('Departments')">
    <x-ui.page-header :title="__('Departments')" :description="__('Departments inside each client company.')">
        <x-slot:actions>
            <x-ui.button :href="route('departments.create', $companyId ? ['company_id' => $companyId] : [])" icon="plus">{{ __('Add department') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="departments" :paginator="$departments" :columns="array_filter(['company' => $companyId ? null : __('Company'), 'parent' => __('Parent department'), 'employees' => __('Employees')])">
        <x-slot:filters>
            <form method="GET" class="relative w-full sm:w-64" role="search">
                @if (request()->has('company_id'))<input type="hidden" name="company_id" value="{{ request('company_id') }}">@endif
                <x-icon name="search" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search departments…') }}" aria-label="{{ __('Search departments') }}" class="control h-8 py-1 ps-8 text-13">
            </form>
        </x-slot:filters>

        <x-slot:head>
            <x-ui.th sort="name" col="name">{{ __('Department') }}</x-ui.th>
            @unless ($companyId)<x-ui.th col="company" class="max-sm:hidden">{{ __('Company') }}</x-ui.th>@endunless
            <x-ui.th col="parent" class="max-md:hidden">{{ __('Parent department') }}</x-ui.th>
            <x-ui.th sort="employees" col="employees" align="end">{{ __('Employees') }}</x-ui.th>
            <th class="w-12 border-b border-line"><span class="sr-only">{{ __('Actions') }}</span></th>
        </x-slot:head>

        @foreach ($departments as $department)
            <tr>
                <td data-col="name"><a href="{{ route('departments.edit', $department) }}" class="font-medium text-ink hover:text-primary">{{ $department->name }}</a></td>
                @unless ($companyId)<td data-col="company" class="text-ink-2 max-sm:hidden">{{ $department->company->establishment_name }}</td>@endunless
                <td data-col="parent" class="text-ink-2 max-md:hidden">{{ $department->parent?->name ?? '—' }}</td>
                <td data-col="employees" class="text-end font-mono tabular">
                    <a href="{{ route('employees.index', ['company_id' => $department->company_id, 'department_id' => $department->id]) }}" class="hover:text-primary hover:underline">{{ $department->employees_count }}</a>
                </td>
                <td class="text-end">
                    <x-ui.row-actions :label="__('Actions for :name', ['name' => $department->name])">
                        <x-ui.menu-item :href="route('departments.edit', $department)" icon="pencil">{{ __('Edit') }}</x-ui.menu-item>
                        <x-ui.menu-item :href="route('employees.index', ['company_id' => $department->company_id, 'department_id' => $department->id])" icon="users">{{ __('View employees') }}</x-ui.menu-item>
                        <div class="my-1 border-t border-line"></div>
                        <x-ui.menu-item danger icon="trash"
                            :confirm="['title' => __('Delete :name?', ['name' => $department->name]), 'message' => __('Only empty departments can be deleted. You can undo this right after.'), 'confirmLabel' => __('Delete department'), 'action' => route('departments.destroy', $department)]">{{ __('Delete') }}</x-ui.menu-item>
                    </x-ui.row-actions>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            <x-ui.empty-state icon="building" :title="request('search') ? __('No departments match') : __('No departments yet')"
                              :message="__('Departments help you group employees inside a company, for example Production, Packing or Stores.')">
                <x-ui.button :href="route('departments.create', $companyId ? ['company_id' => $companyId] : [])" icon="plus">{{ __('Add department') }}</x-ui.button>
            </x-ui.empty-state>
        </x-slot:empty>
    </x-ui.data-table>
</x-app-layout>
