@php
    $scope = $company ? ['company_id' => $company->id] : [];
    $attention = collect([
        ['count' => $missing['aadhaar'], 'label' => __('employees missing Aadhaar'), 'hint' => __('Needed for PF/ESIC registration'), 'href' => route('employees.index', $scope + ['missing' => 'aadhaar']), 'tone' => 'warn'],
        ['count' => $missing['uan'], 'label' => __('employees without a UAN'), 'hint' => __('PF contributions can’t be filed for them'), 'href' => route('employees.index', $scope + ['missing' => 'uan']), 'tone' => 'warn'],
        ['count' => $missing['bank'], 'label' => __('employees missing bank details'), 'hint' => __('Salary can’t be paid by transfer'), 'href' => route('employees.index', $scope + ['missing' => 'bank']), 'tone' => 'warn'],
    ])->filter(fn ($i) => $i['count'] > 0)->values();

    // Modules not built yet: shown so the layout is real, but never with invented numbers.
    $upcoming = [
        ['label' => __('Overdue compliance filings'), 'href' => route('compliance.index')],
        ['label' => __('Missing punches today'), 'href' => route('attendance.missing-punch')],
        ['label' => __('Pending attendance & leave approvals'), 'href' => route('attendance.requests')],
        ['label' => __('Documents expiring in 30 days'), 'href' => route('documents.index')],
    ];
@endphp

<x-app-layout :title="__('Dashboard')">
    <x-ui.page-header :title="__('Dashboard')" :crumbs="[]">
        <x-slot:description>
            {{ now()->format('l, j F Y') }} ·
            @if ($company)
                {{ __('Showing') }} <span class="font-medium text-ink-2">{{ $company->establishment_name }}</span>
            @else
                {{ __('Showing all companies') }}
            @endif
        </x-slot:description>
        <x-slot:actions>
            <x-ui.button :href="route('employees.create', $scope)" icon="plus">{{ __('Add employee') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 xl:grid-cols-3">
        {{-- Needs attention --}}
        <x-ui.card class="xl:col-span-2" :padded="false">
            <x-slot:title>{{ __('Needs attention') }}</x-slot:title>
            <x-slot:actions>
                @if ($attention->isNotEmpty())
                    <x-ui.badge tone="warn" dot>{{ $attention->count() }} {{ trans_choice('item|items', $attention->count()) }}</x-ui.badge>
                @endif
            </x-slot:actions>

            <ul class="divide-y divide-line">
                @forelse ($attention as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="group flex items-center gap-4 px-5 py-3 transition-colors hover:bg-sunken/60">
                            <span class="w-14 shrink-0 text-end font-mono text-lg font-medium tabular text-warn">{{ number_format($item['count']) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium text-ink">{{ $item['label'] }}</span>
                                <span class="block text-13 text-ink-3">{{ $item['hint'] }}</span>
                            </span>
                            <span class="flex items-center gap-1 text-13 font-medium text-primary opacity-70 group-hover:opacity-100">{{ __('Review') }}<x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" /></span>
                        </a>
                    </li>
                @empty
                    <li class="flex items-center gap-3 px-5 py-4 text-sm text-ink-2">
                        <x-icon name="check-circle" class="text-success" />
                        {{ __('Employee records are complete. Nothing to fix right now.') }}
                    </li>
                @endforelse

                @foreach ($upcoming as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="group flex items-center gap-4 px-5 py-2.5 transition-colors hover:bg-sunken/60">
                            <span class="w-14 shrink-0 text-end font-mono text-lg text-ink-3/60">—</span>
                            <span class="min-w-0 flex-1 text-sm text-ink-3">{{ $item['label'] }}</span>
                            <x-ui.badge>{{ __('Not connected yet') }}</x-ui.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        {{-- Quick actions --}}
        <x-ui.card :title="__('Quick actions')" :padded="false" class="self-start">
            <div class="grid grid-cols-2 gap-px bg-line">
                @foreach ([
                    ['Add employee', 'users', route('employees.create', $scope)],
                    ['Add company', 'building', route('company.create')],
                    ['Monthly attendance', 'calendar', route('attendance.index')],
                    ['Run payroll', 'wallet', route('payroll.index')],
                    ['Compliance', 'scale', route('compliance.index')],
                    ['Reports', 'chart', route('reports.index')],
                ] as [$label, $icon, $href])
                    <a href="{{ $href }}" class="flex items-center gap-2.5 bg-surface px-4 py-3.5 text-sm text-ink-2 transition-colors hover:bg-primary-soft/50 hover:text-ink">
                        <x-icon :name="$icon" class="size-[18px] text-ink-3" />{{ __($label) }}
                    </a>
                @endforeach
            </div>
            <p class="border-t border-line px-5 py-2.5 text-xs text-ink-3">{{ __('Tip: press') }} <kbd class="rounded border border-line bg-sunken px-1 font-mono">Ctrl K</kbd> {{ __('to jump to any page or employee.') }}</p>
        </x-ui.card>
    </div>

    {{-- Headline numbers --}}
    <h2 class="mb-2.5 mt-7 text-13 font-semibold text-ink-2">{{ __('Workforce') }}</h2>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-ui.stat-card :label="__('Total employees')" :value="number_format($stats['employees'])" :hint="__('Active on rolls')" :href="route('employees.index', $scope)" />
        @if ($stats['companies'] !== null)
            <x-ui.stat-card :label="__('Client companies')" :value="number_format($stats['companies'])" :href="route('company.index')" />
        @else
            <x-ui.stat-card :label="__('Departments')" :value="number_format($stats['departments'])" :href="route('departments.index', $scope)" />
        @endif
        <x-ui.stat-card :label="__('Joined this month')" :value="number_format($stats['joined'])" :hint="now()->format('F Y')" :href="route('employees.index', $scope + ['sort' => 'joining_date', 'dir' => 'desc'])" />
        <x-ui.stat-card :label="__('Left this month')" :value="number_format($stats['separated'])" :hint="now()->format('F Y')" :href="route('employees.separated', $scope + ['sort' => 'separation_date', 'dir' => 'desc'])" />
    </div>

    <div class="mt-7 grid gap-5 lg:grid-cols-2">
        <section>
            <h2 class="mb-2.5 text-13 font-semibold text-ink-2">{{ __('Attendance today') }}</h2>
            <div class="grid grid-cols-2 gap-3">
                <x-ui.stat-card :label="__('Today present')" pending :href="route('attendance.index')" />
                <x-ui.stat-card :label="__('Today absent')" pending :href="route('attendance.index')" />
                <x-ui.stat-card :label="__('Today on leave')" pending :href="route('attendance.leave')" />
                <x-ui.stat-card :label="__('Missing punch')" pending :href="route('attendance.missing-punch')" />
            </div>
        </section>
        <section>
            <h2 class="mb-2.5 text-13 font-semibold text-ink-2">{{ __('Compliance this month') }}</h2>
            <div class="grid grid-cols-2 gap-3">
                <x-ui.stat-card :label="__('Total compliances')" pending :href="route('compliance.index')" />
                <x-ui.stat-card :label="__('Pending')" pending :href="route('compliance.index')" />
                <x-ui.stat-card :label="__('Due this week')" pending :href="route('compliance.index')" />
                <x-ui.stat-card :label="__('Overdue')" pending :href="route('compliance.index')" />
            </div>
        </section>
    </div>
</x-app-layout>
