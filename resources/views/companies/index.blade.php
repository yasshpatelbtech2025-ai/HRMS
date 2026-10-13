<x-app-layout :title="__('Companies')">
    <x-ui.page-header :title="__('Companies')" :description="__('Client companies you supply staff to.')" :crumbs="[]">
        <x-slot:actions>
            <x-ui.button :href="route('company.create')" icon="plus">{{ __('Add company') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table id="companies" :paginator="$companies" :noun="[__('company'), __('companies')]"
                     :columns="['employees' => __('Employees'), 'departments' => __('Departments'), 'size' => __('Organisation size'), 'location' => __('Location'), 'parent' => __('Connected company')]">
        <x-slot:filters>
            <form method="GET" class="relative w-full sm:w-72" role="search">
                <x-icon name="search" class="pointer-events-none absolute start-2.5 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name…') }}" aria-label="{{ __('Search companies') }}" class="control h-8 py-1 ps-8 text-13">
                @foreach (request()->only('sort', 'dir', 'per_page') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            </form>
            @if (request('search'))
                <a href="{{ request()->fullUrlWithoutQuery(['search', 'page']) }}" class="text-13 text-ink-3 hover:text-ink">{{ __('Clear search') }}</a>
            @endif
        </x-slot:filters>

        <x-slot:head>
            <x-ui.th sort="name" col="name">{{ __('Establishment') }}</x-ui.th>
            <x-ui.th sort="employees" col="employees" align="end">{{ __('Employees') }}</x-ui.th>
            <x-ui.th col="departments" align="end" class="max-md:hidden">{{ __('Departments') }}</x-ui.th>
            <x-ui.th sort="size" col="size" align="end" class="max-lg:hidden">{{ __('Org. size') }}</x-ui.th>
            <x-ui.th sort="district" col="location" class="max-md:hidden">{{ __('Location') }}</x-ui.th>
            <x-ui.th col="parent" class="max-lg:hidden">{{ __('Connected company') }}</x-ui.th>
            <th class="w-12 border-b border-line"><span class="sr-only">{{ __('Actions') }}</span></th>
        </x-slot:head>

        @foreach ($companies as $company)
            <tr>
                <td data-col="name">
                    <a href="{{ route('company.edit', $company) }}" class="flex items-center gap-2.5 font-medium text-ink hover:text-primary">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-[5px] text-[10px] font-semibold text-white" style="background: hsl({{ \App\Support\CurrentCompany::hue($company->id) }} 42% 38%)" aria-hidden="true">{{ mb_strtoupper(mb_substr($company->establishment_name, 0, 2)) }}</span>
                        <span class="truncate">{{ $company->establishment_name }}</span>
                    </a>
                </td>
                <td data-col="employees" class="text-end font-mono tabular">
                    <a href="{{ route('employees.index', ['company_id' => $company->id]) }}" class="hover:text-primary hover:underline">{{ number_format($company->employees_count) }}</a>
                </td>
                <td data-col="departments" class="text-end font-mono tabular text-ink-2 max-md:hidden">{{ $company->departments_count }}</td>
                <td data-col="size" class="text-end font-mono tabular text-ink-2 max-lg:hidden">{{ $company->organization_size ? number_format($company->organization_size) : '—' }}</td>
                <td data-col="location" class="text-ink-2 max-md:hidden">{{ collect([$company->taluka, $company->district])->filter()->implode(', ') ?: '—' }}</td>
                <td data-col="parent" class="text-ink-2 max-lg:hidden">{{ $company->parent?->establishment_name ?? '—' }}</td>
                <td class="text-end">
                    <x-ui.row-actions :label="__('Actions for :name', ['name' => $company->establishment_name])">
                        <x-ui.menu-item :href="route('company.edit', $company)" icon="pencil">{{ __('Edit') }}</x-ui.menu-item>
                        <form method="POST" action="{{ route('context.company') }}">
                            @csrf
                            <x-ui.menu-item type="submit" name="company_id" :value="$company->id" icon="building">{{ __('Work in this company') }}</x-ui.menu-item>
                        </form>
                        <div class="my-1 border-t border-line"></div>
                        <x-ui.menu-item danger icon="trash"
                            :confirm="['title' => __('Delete :name?', ['name' => $company->establishment_name]), 'message' => __('It will be removed from lists. You can undo this right after.'), 'confirmLabel' => __('Delete company'), 'action' => route('company.destroy', $company)]">{{ __('Delete') }}</x-ui.menu-item>
                    </x-ui.row-actions>
                </td>
            </tr>
        @endforeach

        <x-slot:empty>
            @if (request('search'))
                <x-ui.empty-state icon="search" :title="__('No companies match “:term”', ['term' => request('search')])" :message="__('Check the spelling, or search for part of the name.')">
                    <x-ui.button variant="secondary" :href="route('company.index')">{{ __('Clear search') }}</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state icon="building" :title="__('No companies yet')" :message="__('Add the client companies you supply staff to. Employees, attendance and payroll are all organised by company.')">
                    <x-ui.button :href="route('company.create')" icon="plus">{{ __('Add your first company') }}</x-ui.button>
                </x-ui.empty-state>
            @endif
        </x-slot:empty>
    </x-ui.data-table>
</x-app-layout>
