<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    @php
        $filterKeys = ['company_id', 'name', 'employee_code', 'employment_type', 'department_id', 'sub_department_id', 'position_id', 'blood_group', 'country', 'gender', 'marital_status'];
        // A company that came from the top-bar picker isn't a filter the user opened.
        $filtering = collect(request()->only($filterKeys))
            ->when(request()->attributes->get('company_from_picker'), fn ($f) => $f->except('company_id'))
            ->filter(fn ($v) => filled($v))->isNotEmpty();
        $exportQuery = request()->only([...$filterKeys, 'status', 'search', 'sort', 'direction']);
        $status = in_array(request('status'), ['active', 'inactive'], true) ? request('status') : null;
        $statusUrl = fn (?string $value) => route('employees.index', array_filter(['status' => $value, 'per_page' => request('per_page')]));
        $select = 'control';
    @endphp

    @if ($importRun)
        @include('employees._import-status', ['run' => $importRun])
    @endif

    <div x-data="{ filtersOpen: @js($filtering) }">
        @php $activeFilters = collect(request()->only($filterKeys))->when(request()->attributes->get('company_from_picker'), fn ($f) => $f->except('company_id'))->filter(fn ($v) => filled($v))->count(); @endphp
        <x-ui.page-header :title="__('Employee list')" :description="__('Everyone on your client sites. Click a name to open their profile.')">
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="funnel" type="button" x-on:click="filtersOpen = ! filtersOpen" ::aria-expanded="filtersOpen">
                    {{ __('Filter') }}@if ($activeFilters)<span class="ms-0.5 rounded-full bg-primary px-1.5 text-xs font-medium text-on-primary">{{ $activeFilters }}</span>@endif
                </x-ui.button>
                <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('open-modal', 'import-employees')">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    {{ __('Import Employees') }}
                </x-ui.button>
                <x-ui.button :href="route('employees.create')" icon="plus">{{ __('Add employee') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    <x-stat-strip :items="[
            ['label' => __('All employees'), 'value' => $stats['total'], 'href' => $statusUrl(null), 'active' => ! $status],
            ['label' => __('Active'), 'value' => $stats['active'], 'tone' => 'success', 'href' => $statusUrl('active'), 'active' => $status === 'active'],
            ['label' => __('Inactive'), 'value' => $stats['inactive'], 'tone' => $stats['inactive'] ? 'warn' : null, 'href' => $statusUrl('inactive'), 'active' => $status === 'inactive'],
            ['label' => __('Joined this month'), 'value' => $stats['joined'], 'hint' => now()->format('F Y')],
        ]" />

        <div class="card">

        {{-- Filter panel --}}
        <form method="GET" action="{{ route('employees.index') }}" x-show="filtersOpen" x-collapse
              class="grid grid-cols-1 gap-x-4 gap-y-3 border-b border-line bg-sunken/50 px-4 py-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 print:hidden" style="display: none;">
            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Company') }}</span><select name="company_id" class="{{ $select }}" aria-label="{{ __('Company') }}">
                <option value="">{{ __('Select company') }}</option>
                @foreach ($filters['companies'] as $company)
                    <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->label }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Employee name') }}</span><x-text-input name="name" :value="request('name')" :placeholder="__('Employee name')" class="text-sm" aria-label="{{ __('Employee name') }}" /></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Employee id') }}</span><x-text-input name="employee_code" :value="request('employee_code')" :placeholder="__('Employee id')" class="text-sm" aria-label="{{ __('Employee id') }}" /></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Employee type') }}</span><select name="employment_type" class="{{ $select }}" aria-label="{{ __('Employee type') }}">
                <option value="">{{ __('Select employee type') }}</option>
                @foreach (\App\Enums\EmploymentType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(request('employment_type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Department') }}</span><select name="department_id" class="{{ $select }}" aria-label="{{ __('Department') }}">
                <option value="">{{ __('All department') }}</option>
                @foreach ($filters['departments'] as $department)
                    <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Sub department') }}</span><select name="sub_department_id" class="{{ $select }}" aria-label="{{ __('Sub department') }}">
                <option value="">{{ __('Select sub department') }}</option>
                @foreach ($filters['subDepartments'] as $subDepartment)
                    <option value="{{ $subDepartment->id }}" @selected(request('sub_department_id') == $subDepartment->id)>{{ $subDepartment->name }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Designation') }}</span><select name="position_id" class="{{ $select }}" aria-label="{{ __('Designation') }}">
                <option value="">{{ __('All designation') }}</option>
                @foreach ($filters['positions'] as $position)
                    <option value="{{ $position->id }}" @selected(request('position_id') == $position->id)>{{ $position->name }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Blood group') }}</span><select name="blood_group" class="{{ $select }}" aria-label="{{ __('Blood group') }}">
                <option value="">{{ __('All blood group') }}</option>
                @foreach (config('employee.blood_groups') as $group)
                    <option value="{{ $group }}" @selected(request('blood_group') === $group)>{{ $group }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Country') }}</span><select name="country" class="{{ $select }}" aria-label="{{ __('Country') }}">
                <option value="">{{ __('All country') }}</option>
                @foreach (config('employee.countries') as $country)
                    <option value="{{ $country }}" @selected(request('country') === $country)>{{ $country }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Gender') }}</span><select name="gender" class="{{ $select }}" aria-label="{{ __('Gender') }}">
                <option value="">{{ __('All gender') }}</option>
                @foreach (\App\Enums\Gender::cases() as $gender)
                    <option value="{{ $gender->value }}" @selected(request('gender') === $gender->value)>{{ $gender->label() }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Marital status') }}</span><select name="marital_status" class="{{ $select }}" aria-label="{{ __('Marital status') }}">
                <option value="">{{ __('All marital status') }}</option>
                @foreach (config('employee.marital_statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(request('marital_status') === $value)>{{ $label }}</option>
                @endforeach
            </select></label>
            <div class="flex items-end gap-2">
                <button class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ __('Find') }}</button>
                <a href="{{ route('employees.index') }}" class="inline-flex h-control items-center rounded-control px-3 text-sm font-medium text-ink-2 hover:bg-sunken hover:text-ink">{{ __('Reset') }}</a>
            </div>
        </form>

        <x-list-toolbar :action="route('employees.index')" :per-page="$perPage" allow-all :exports="[
            __('CSV') => route('employees.export', ['format' => 'csv'] + $exportQuery),
            __('Excel') => route('employees.export', ['format' => 'xlsx'] + $exportQuery),
        ]">
            {{-- Extra columns saved for your login (server side), unlike "Columns" which only hides. --}}
            <x-ui.menu width="w-56">
                <x-slot:trigger>
                    <button type="button" class="inline-flex h-8 items-center gap-1.5 rounded-control px-2.5 text-13 font-medium text-ink-2 hover:bg-sunken hover:text-ink" aria-haspopup="menu">
                        <x-icon name="plus" class="size-4" /><span class="max-sm:sr-only">{{ __('Select Columns') }}</span>
                    </button>
                </x-slot:trigger>
                <p class="px-2.5 pb-1 pt-1.5 text-xs font-medium text-ink-3">{{ __('Add columns to this list') }}</p>
                @foreach (config('employee.optional_columns') as $key => [$label])
                    <form method="POST" action="{{ route('employees.columns') }}">
                        @csrf
                        <input type="hidden" name="column" value="{{ $key }}">
                        <button class="flex w-full items-center justify-between gap-2 rounded-[5px] px-2.5 py-1.5 text-start text-sm text-ink hover:bg-sunken focus:bg-sunken focus:outline-none" role="menuitemcheckbox" aria-checked="{{ $columns[$key] ? 'true' : 'false' }}">
                            {{ __($label) }}
                            @if ($columns[$key])<x-icon name="check" class="size-4 text-primary" />@endif
                        </button>
                    </form>
                @endforeach
            </x-ui.menu>
        </x-list-toolbar>

        <div class="overflow-x-auto" x-data="bulkSelect">
            <x-bulk-bar :action="route('employees.bulk')" noun="employee|employees"
                        :exports="[__('Export CSV') => route('employees.export', 'csv'), __('Export Excel') => route('employees.export', 'xlsx')]"
                        :actions="['activate' => [__('Mark active'), 'default', null], 'deactivate' => [__('Mark inactive'), 'default', null], 'delete' => [__('Delete'), 'danger', 'Delete :count selected employee(s)? They can be restored from Configs › Recently deleted.']]" />
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="w-10 px-4 py-3"><input type="checkbox" class="h-4 w-4 rounded border-field text-primary focus:ring-primary/30" :checked="allOnPage()" :indeterminate="someOnPage()" x-on:change="toggleAll($event.target.checked)" aria-label="{{ __('Select all on this page') }}"></th>
                        <x-sort-header column="name" class="px-4 py-3">{{ __('Employee') }}</x-sort-header>
                        <x-sort-header column="company" default class="px-4 py-3">{{ __('Company') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Contact') }}</th>
                        <th class="px-4 py-3">{{ __('Department') }}</th>
                        <x-sort-header column="status" class="px-4 py-3">{{ __('Status') }}</x-sort-header>
                        <th class="px-4 py-3"><span class="sr-only">{{ __('Action') }}</span></th>
                        @if ($columns['date_of_birth'])<th class="px-4 py-3">{{ __('Date of Birth') }}</th>@endif
                        @if ($columns['designation'])<th class="px-4 py-3">{{ __('Designation') }}</th>@endif
                        @if ($columns['joining_date'])<x-sort-header column="joining_date" class="px-4 py-3">{{ __('Joining Date') }}</x-sort-header>@endif
                        @if ($columns['gender'])<th class="px-4 py-3">{{ __('Gender') }}</th>@endif
                        @if ($columns['shift'])<x-sort-header column="shift" class="px-4 py-3">{{ __('Shift') }}</x-sort-header>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($employees as $employee)
                        <tr :class="selected.includes(@js($employee->uuid)) && '!bg-primary-soft/60'">
                            <td class="px-4 py-3"><input type="checkbox" class="h-4 w-4 rounded border-field text-primary focus:ring-primary/30" value="{{ $employee->uuid }}" x-model="selected" data-bulk-id aria-label="{{ __('Select :name', ['name' => $employee->full_name]) }}"></td>
                            <td class="px-4 py-3">
                                <a href="{{ route('employees.show', $employee) }}" class="group flex items-center gap-3">
                                    <x-employee-photo :employee="$employee" class="h-9 w-9 shrink-0" />
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-ink group-hover:text-primary group-hover:underline">{{ $employee->full_name }}</span>
                                        <span class="block font-mono text-xs text-ink-3">{{ $employee->employee_code }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $employee->company?->establishment_name }}</td>
                            <td class="px-4 py-3">
                                <span class="block whitespace-nowrap">{{ $employee->phone }}</span>
                                @if ($columns['email'] && $employee->email)<span class="block text-xs text-ink-3">{{ $employee->email }}</span>@endif
                            </td>
                            <td class="px-4 py-3">{{ $employee->subDepartment?->name ?? $employee->department?->name }}</td>
                            <td class="px-4 py-3"><x-status-badge :active="$employee->status === \App\Enums\EmployeeStatus::Active" /></td>
                            <td class="px-4 py-3">
                                <x-row-menu>
                                    @php $makeInactive = $employee->status === \App\Enums\EmployeeStatus::Active; @endphp
                                    <form method="POST" action="{{ route('employees.status', $employee) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button title="{{ $makeInactive ? __('Make inactive') : __('Make active') }}" class="rounded-md p-2 {{ $makeInactive ? 'text-amber-600 hover:bg-warn-soft' : 'text-success hover:bg-success-soft' }}">
                                            <span class="sr-only">{{ $makeInactive ? __('Make inactive') : __('Make active') }}</span>
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" /></svg>
                                        </button>
                                    </form>
                                    <a href="{{ route('employees.show', $employee) }}" title="{{ __('Show') }}" class="rounded-md p-2 text-info hover:bg-info-soft">
                                        <span class="sr-only">{{ __('Show') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </a>
                                    <a href="{{ route('employees.edit', $employee) }}" title="{{ __('Edit') }}" class="rounded-md p-2 text-brand-600 hover:bg-brand-50">
                                        <span class="sr-only">{{ __('Edit') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </a>
                                    @can('delete-records')
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" data-confirm="{{ __('Delete employee :name?', ['name' => $employee->full_name]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button title="{{ __('Delete') }}" class="rounded-md p-2 row-menu-danger text-danger hover:bg-danger-soft">
                                                <span class="sr-only">{{ __('Delete') }}</span>
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                            </button>
                                        </form>
                                    @endcan
                                    <a href="{{ route('payroll.revisions', ['employee' => $employee->uuid]) }}" title="{{ __('Salary Revision') }}" class="rounded-md p-2 text-gray-600 hover:bg-gray-100">
                                        <span class="sr-only">{{ __('Salary Revision') }}</span>
                                        <span aria-hidden="true" class="block h-4 w-4 text-center text-sm font-semibold leading-4">₹</span>
                                    </a>
                                    <button type="button" title="{{ __('Separation') }}" class="rounded-md p-2 text-warn hover:bg-warn-soft"
                                            x-on:click="$dispatch('separate-employee', @js(['action' => route('employees.separation', $employee), 'name' => $employee->full_name, 'date' => $employee->separation_date?->toDateString()]))">
                                        <span class="sr-only">{{ __('Separation') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                                    </button>
                                    <button type="button" title="{{ __('Letters') }}" class="rounded-md p-2 text-gray-600 hover:bg-gray-100"
                                        x-on:click="$dispatch('show-letters', @js(['name' => $employee->full_name, 'call' => route('employees.letter', [$employee, 'call']), 'offer' => route('employees.letter', [$employee, 'offer']), 'joining' => route('employees.letter', [$employee, 'joining'])]))">
                                    <span class="sr-only">{{ __('Letters') }}</span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                </button>
                                </x-row-menu>
                            </td>
                            @if ($columns['date_of_birth'])<td class="px-4 py-3 whitespace-nowrap">{{ $employee->date_of_birth?->toDateString() }}</td>@endif
                            @if ($columns['designation'])<td class="px-4 py-3">{{ $employee->position?->name }}</td>@endif
                            @if ($columns['joining_date'])<td class="px-4 py-3 whitespace-nowrap">{{ $employee->date_of_joining?->toDateString() }}</td>@endif
                            @if ($columns['gender'])<td class="px-4 py-3">{{ $employee->gender?->label() }}</td>@endif
                            @if ($columns['shift'])<td class="px-4 py-3">{{ $employee->shift?->name }}</td>@endif
                        </tr>
                    @empty
                        @if ($filtering || $status || request('search'))
                            <x-empty-state colspan="17" icon="search" :title="__('No employees match these filters.')">{{ __('Try a different search or clear the filters.') }}</x-empty-state>
                        @else
                            <x-empty-state colspan="17" icon="users" :title="__('No employees yet')">
                                {{ __('Add your first employee to start tracking attendance, payroll and compliance.') }}
                                <x-slot name="action"><x-ui.button :href="route('employees.create')" icon="plus">{{ __('Add employee') }}</x-ui.button></x-slot>
                            </x-empty-state>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$employees" />
    </div>
    </div>

    @include('employees._separation-modal')

    {{-- Letters --}}
    <div x-data="{ letters: null }" x-on:show-letters.window="letters = $event.detail; $dispatch('open-modal', 'employee-letters')">
        <x-modal name="employee-letters" max-width="md">
            <div class="border-b border-line px-6 py-4">
                <h2 class="text-lg font-semibold text-ink">{{ __("Employee's Letters") }}</h2>
                <p class="text-sm text-ink-2" x-text="letters?.name"></p>
            </div>
            <div class="flex flex-col gap-2 px-6 py-5">
                <a :href="letters?.call" target="_blank" class="rounded-md border border-brand-300 px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-50">📄 {{ __('Call Letter') }}</a>
                <a :href="letters?.offer" target="_blank" class="rounded-md border border-info/30 px-4 py-2 text-sm font-medium text-info hover:bg-info-soft">📄 {{ __('Offer Letter') }}</a>
                <a :href="letters?.joining" target="_blank" class="rounded-md border border-amber-300 px-4 py-2 text-sm font-medium text-warn hover:bg-warn-soft">📄 {{ __('Joining Letter') }}</a>
            </div>
            <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
            </div>
        </x-modal>
    </div>

    {{-- Import --}}
    <x-modal name="import-employees" :show="$errors->has('file') || $errors->has('company_id')" max-width="2xl">
        <form method="POST" action="{{ route('employees.import') }}" enctype="multipart/form-data"
              x-data="{ company: @js(old('company_id', '')), templates: @js($importCompanies->mapWithKeys(fn ($c) => [$c->id => route('employees.import.template', $c)])) }">
            @csrf
            <div class="border-b border-line px-6 py-4">
                <h2 class="text-lg font-semibold text-ink">{{ __('Import Employees (Excel)') }}</h2>
            </div>
            <div class="space-y-4 px-6 py-5">
                <x-form-row :label="__('Company')" for="import_company_id" required :messages="$errors->get('company_id')">
                    <select id="import_company_id" name="company_id" x-model="company" required class="{{ $select }}">
                        <option value="">{{ __('Select company') }}</option>
                        @foreach ($importCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->label }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row :label="__('Excel Template')" x-show="company" x-transition="">
                    <a :href="templates[company]" class="inline-flex h-control-sm items-center gap-2 rounded-control border border-line-strong px-3 text-13 font-medium text-primary hover:bg-primary-soft/50"><x-icon name="download" class="size-4" />
                        {{ __('Download Company-Specific Template') }}
                    </a>
                    <p class="mt-1 text-xs text-gray-500">{{ __("The template's Lists sheet shows the departments, sub departments, designations and shifts this company accepts.") }}</p>
                </x-form-row>
                <x-form-row :label="__('Excel File')" for="import_file" required :messages="$errors->get('file')">
                    <input type="file" id="import_file" name="file" accept=".xlsx,.csv" required
                           class="block w-full text-sm text-ink-2 file:me-3 file:h-control-sm file:cursor-pointer file:rounded-control file:border file:border-line-strong file:bg-surface file:px-3 file:text-13 file:font-medium file:text-ink hover:file:bg-sunken">
                    <p class="mt-1 text-xs text-gray-500">{{ __('Acceptable formats: .xlsx, .csv. Maximum file size: 10MB.') }}</p>
                </x-form-row>
            </div>
            <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
                <x-primary-button>{{ __('Import') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
