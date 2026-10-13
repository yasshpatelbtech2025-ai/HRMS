@php
    $filters = array_filter(request()->only('department_id', 'position_id', 'missing', 'search'));
    $deptName = request('department_id') ? $departments->firstWhere('id', (int) request('department_id'))?->name : null;
    $posName = request('position_id') ? $positions->firstWhere('id', (int) request('position_id'))?->name : null;
    $chips = array_filter([
        'search' => request('search') ? '“'.request('search').'”' : null,
        'department_id' => $deptName ? __('Department').': '.$deptName : null,
        'position_id' => $posName ? __('Designation').': '.$posName : null,
        'missing' => isset(\App\Http\Controllers\EmployeeController::MISSING[request('missing')]) ? __(\App\Http\Controllers\EmployeeController::MISSING[request('missing')]) : null,
    ]);
    $showCompany = ! $companyId;
@endphp

<x-app-layout :title="$title">
    <x-ui.page-header :title="$title">
        <x-slot:description>
            @if ($companyId)
                {{ $companies->firstWhere('id', $companyId)?->establishment_name }}
            @else
                {{ __('All companies') }}
            @endif
        </x-slot:description>
        <x-slot:actions>
            @unless ($separatedView)
                <x-ui.button :href="route('employees.create', $companyId ? ['company_id' => $companyId] : [])" icon="plus">{{ __('Add employee') }}</x-ui.button>
            @endunless
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table :id="$separatedView ? 'employees-separated' : 'employees'" :paginator="$employees" :bulk-delete="route('employees.bulk-destroy')"
                     :noun="[__('employee'), __('employees')]"
                     :columns="array_filter([
                         'company' => $showCompany ? __('Company') : null,
                         'mobile' => __('Mobile'), 'department' => __('Department'), 'designation' => __('Designation'),
                         'joined' => __('Joining date'), 'separated' => $separatedView ? __('Separation date') : null,
                         'kyc' => __('Records'),
                     ])">
        <x-slot:filters>
            <form method="GET" x-data="{ more: {{ ($deptName || $posName || request('missing')) ? 'true' : 'false' }} }" class="flex w-full flex-wrap items-center gap-2">
                @if (request()->has('company_id'))<input type="hidden" name="company_id" value="{{ request('company_id') }}">@endif
                @foreach (request()->only('sort', 'dir', 'per_page') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach

                <div class="relative w-full sm:w-64" role="search">
                    <x-icon name="search" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Name, ID or mobile') }}" aria-label="{{ __('Search employees') }}" class="control h-8 py-1 ps-8 text-13">
                </div>

                <select name="department_id" onchange="this.form.submit()" aria-label="{{ __('Department') }}" class="control h-8 w-auto py-1 pe-8 text-13 max-sm:flex-1">
                    <option value="">{{ __('All departments') }}</option>
                    @foreach ($departments->when($companyId, fn ($c) => $c->where('company_id', $companyId)) as $d)
                        <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                <select name="position_id" onchange="this.form.submit()" aria-label="{{ __('Designation') }}" class="control h-8 w-auto py-1 pe-8 text-13 max-md:hidden">
                    <option value="">{{ __('All designations') }}</option>
                    @foreach ($positions as $p)
                        <option value="{{ $p->id }}" @selected(request('position_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
                <select name="missing" onchange="this.form.submit()" aria-label="{{ __('Record gaps') }}" class="control h-8 w-auto py-1 pe-8 text-13 max-md:hidden">
                    <option value="">{{ __('Any records') }}</option>
                    @foreach (\App\Http\Controllers\EmployeeController::MISSING as $key => $label)
                        <option value="{{ $key }}" @selected(request('missing') === $key)>{{ __($label) }}</option>
                    @endforeach
                </select>
                <button class="sr-only">{{ __('Apply filters') }}</button>
            </form>
        </x-slot:filters>

        @if ($chips)
            <x-slot:chips>
                @foreach ($chips as $key => $label)
                    <a href="{{ request()->fullUrlWithoutQuery([$key, 'page']) }}" class="inline-flex items-center gap-1 rounded-full border border-line bg-surface py-0.5 pe-1.5 ps-2.5 text-xs text-ink-2 hover:border-line-strong hover:text-ink" aria-label="{{ __('Remove filter') }} {{ $label }}">
                        {{ $label }}<x-icon name="x" class="size-3.5" />
                    </a>
                @endforeach
                @if (count($chips) > 1)
                    <a href="{{ request()->fullUrlWithoutQuery(['search', 'department_id', 'position_id', 'missing', 'page']) }}" class="text-xs text-ink-3 hover:text-ink">{{ __('Clear all') }}</a>
                @endif
            </x-slot:chips>
        @endif

        <x-slot:head>
            <x-ui.th sort="code" col="code">{{ __('ID') }}</x-ui.th>
            <x-ui.th sort="name" col="name">{{ __('Name') }}</x-ui.th>
            @if ($showCompany)<x-ui.th col="company" class="max-md:hidden">{{ __('Company') }}</x-ui.th>@endif
            <x-ui.th col="mobile" class="max-lg:hidden">{{ __('Mobile') }}</x-ui.th>
            <x-ui.th col="department" class="max-md:hidden">{{ __('Department') }}</x-ui.th>
            <x-ui.th col="designation" class="max-xl:hidden">{{ __('Designation') }}</x-ui.th>
            <x-ui.th sort="joining_date" col="joined" class="max-sm:hidden">{{ __('Joined') }}</x-ui.th>
            @if ($separatedView)<x-ui.th sort="separation_date" col="separated">{{ __('Separated') }}</x-ui.th>@endif
            <x-ui.th col="kyc" class="max-lg:hidden">{{ __('Records') }}</x-ui.th>
            <th class="w-12 border-b border-line"><span class="sr-only">{{ __('Actions') }}</span></th>
        </x-slot:head>

        @foreach ($employees as $employee)
            @php
                $gaps = array_filter([
                    'Aadhaar' => ! $employee->aadhaar_number,
                    'UAN' => ! $employee->uan_number,
                    'Bank' => ! $employee->bank_account_number || ! $employee->ifsc_code,
                ]);
            @endphp
            <tr>
                <x-ui.td-select :value="$employee->uuid" :label="$employee->full_name" />
                <td data-col="code" class="w-px whitespace-nowrap font-mono text-ink-2">{{ $employee->employee_code }}</td>
                <td data-col="name">
                    <a href="{{ route('employees.edit', $employee) }}" class="font-medium text-ink hover:text-primary">{{ $employee->full_name }}</a>
                    <span class="block text-xs text-ink-3 md:hidden">{{ collect([$employee->department?->name, $employee->mobile])->filter()->implode(' · ') }}</span>
                </td>
                @if ($showCompany)<td data-col="company" class="max-w-[14rem] truncate text-ink-2 max-md:hidden">{{ $employee->company->establishment_name }}</td>@endif
                <td data-col="mobile" class="whitespace-nowrap font-mono text-ink-2 max-lg:hidden">{{ $employee->mobile ?? '—' }}</td>
                <td data-col="department" class="text-ink-2 max-md:hidden">{{ $employee->department?->name ?? '—' }}</td>
                <td data-col="designation" class="text-ink-2 max-xl:hidden">{{ $employee->position?->name ?? '—' }}</td>
                <td data-col="joined" class="whitespace-nowrap tabular text-ink-2 max-sm:hidden">{{ $employee->joining_date->format('d M Y') }}</td>
                @if ($separatedView)<td data-col="separated" class="whitespace-nowrap tabular text-ink-2">{{ $employee->separation_date?->format('d M Y') ?? '—' }}</td>@endif
                <td data-col="kyc" class="max-lg:hidden">
                    @if ($gaps)
                        <span class="flex flex-wrap gap-1">
                            @foreach ($gaps as $label => $_)<x-ui.badge tone="warn">{{ __('No :doc', ['doc' => $label]) }}</x-ui.badge>@endforeach
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs text-success"><x-icon name="check" class="size-3.5" stroke="2" />{{ __('Complete') }}</span>
                    @endif
                </td>
                <td class="text-end">
                    <x-ui.row-actions :label="__('Actions for :name', ['name' => $employee->full_name])">
                        <x-ui.menu-item :href="route('employees.edit', $employee)" icon="pencil">{{ __('Edit') }}</x-ui.menu-item>
                        <div class="my-1 border-t border-line"></div>
                        <x-ui.menu-item danger icon="trash"
                            :confirm="['title' => __('Delete :name?', ['name' => $employee->full_name]), 'message' => __('They will be removed from all lists. You can undo this right after.'), 'confirmLabel' => __('Delete employee'), 'action' => route('employees.destroy', $employee)]">{{ __('Delete') }}</x-ui.menu-item>
                    </x-ui.row-actions>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            @if ($filters)
                <x-ui.empty-state icon="funnel" :title="__('No employees match these filters')" :message="__('Try removing a filter or searching for part of the name.')">
                    <x-ui.button variant="secondary" :href="request()->fullUrlWithoutQuery(['search', 'department_id', 'position_id', 'missing', 'page'])">{{ __('Clear filters') }}</x-ui.button>
                </x-ui.empty-state>
            @elseif ($separatedView)
                <x-ui.empty-state icon="users" :title="__('No separated employees')" :message="__('When someone leaves, edit their record and set the status to Separated. They will appear here.')" />
            @else
                <x-ui.empty-state icon="users" :title="__('No employees yet')" :message="$companyId ? __('Add the first employee for this company.') : __('Add employees one by one. Each employee belongs to a client company.')">
                    <x-ui.button :href="route('employees.create', $companyId ? ['company_id' => $companyId] : [])" icon="plus">{{ __('Add employee') }}</x-ui.button>
                </x-ui.empty-state>
            @endif
        </x-slot:empty>
    </x-ui.data-table>
</x-app-layout>
