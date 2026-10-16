<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    @php
        $cards = [
            ['label' => __('Total employee'), 'value' => $stats['total'], 'accent' => 'bg-primary', 'href' => route('employees.index', $company ? ['company_id' => $company->id] : [])],
            ['label' => __('Today presents'), 'value' => $stats['present'], 'accent' => 'bg-success', 'href' => route('attendance.index', $company ? ['company' => $company->uuid] : [])],
            ['label' => __('Today absents'), 'value' => $stats['absent'], 'accent' => 'bg-danger', 'href' => route('attendance.index', $company ? ['company' => $company->uuid] : [])],
            ['label' => __('Today leave'), 'value' => $stats['leave'], 'accent' => 'bg-warn', 'href' => route('attendance.leave', $company ? ['company' => $company->uuid] : [])],
        ];
        // Status colours (good / warning / critical) with a neutral for days off; every bar also carries its counts as text.
        $segments = [
            'present' => ['label' => __('Present'), 'class' => 'bg-success'],
            'leave' => ['label' => __('Leave'), 'class' => 'bg-warn'],
            'absent' => ['label' => __('Absent'), 'class' => 'bg-danger'],
            'off' => ['label' => __('Week off / holiday'), 'class' => 'bg-gray-300'],
        ];
    @endphp

    @php
        $scope = $company ? ['company' => $company->uuid] : [];
        $attentionItems = collect([
            ['count' => $attention['overdue'], 'label' => __('compliance tasks overdue'), 'hint' => __('Past their due date in the To Do List'), 'href' => route('compliance.to-do', $scope + ['status' => 'overdue']), 'tone' => 'text-danger'],
            ['count' => $attention['missing'], 'label' => __('missing punches this month'), 'hint' => __('Fix them before salary is generated'), 'href' => route('attendance.missing-punch', $scope), 'tone' => 'text-warn'],
            ['count' => $attention['attendanceRequests'], 'label' => __('attendance requests waiting'), 'hint' => __('Approve or reject them'), 'href' => route('attendance.requests', $scope + ['status' => 'pending']), 'tone' => 'text-info'],
            ['count' => $attention['leaveRequests'], 'label' => __('leave applications waiting'), 'hint' => __('Approve or reject them'), 'href' => route('attendance.leave', $scope + ['status' => 'pending']), 'tone' => 'text-info'],
            ['count' => $attention['week'], 'label' => __('compliance tasks due this week'), 'hint' => __('Coming up in the To Do List'), 'href' => route('compliance.to-do', $scope + ['status' => 'week']), 'tone' => 'text-warn'],
        ])->filter(fn ($item) => $item['count'] > 0)->values();
    @endphp

    <x-ui.page-header :title="__('Dashboard')">
        <x-slot:description>
            {{ $today->format('l, j F Y') }} ·
            @if ($company)
                {{ __('Showing') }} <span class="font-medium text-ink-2">{{ $company->establishment_name }}</span>
            @else
                {{ __('Showing all companies') }}
            @endif
        </x-slot:description>
        <x-slot:actions>
            <x-ui.button :href="route('employees.create')" icon="plus">{{ __('Add employee') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid gap-5 xl:grid-cols-3">
        {{-- Needs attention: real counts, most urgent first, each opening its list. --}}
        <section class="card self-start xl:col-span-2">
            <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3.5">
                <h2 class="text-sm font-semibold text-ink">{{ __('Needs attention') }}</h2>
                @if ($attentionItems->isNotEmpty())
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-warn-soft px-2 py-0.5 text-xs font-medium text-warn ring-1 ring-inset ring-warn/20">
                        <span class="size-1.5 rounded-full bg-current"></span>{{ trans_choice(':count item|:count items', $attentionItems->count()) }}
                    </span>
                @endif
            </header>
            <ul class="divide-y divide-line">
                @forelse ($attentionItems as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="group flex items-center gap-4 px-5 py-3 transition-colors hover:bg-sunken/60">
                            <span class="w-14 shrink-0 text-end font-mono text-lg font-medium tabular {{ $item['tone'] }}">{{ number_format($item['count']) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium text-ink">{{ $item['label'] }}</span>
                                <span class="block text-13 text-ink-3">{{ $item['hint'] }}</span>
                            </span>
                            <span class="flex items-center gap-1 text-13 font-medium text-primary opacity-70 group-hover:opacity-100">{{ __('Review') }}<x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" /></span>
                        </a>
                    </li>
                @empty
                    <li class="flex items-center gap-3 px-5 py-5 text-sm text-ink-2">
                        <x-icon name="check-circle" class="text-success" />
                        {{ __('All clear: nothing overdue, no missing punches and no requests waiting.') }}
                    </li>
                @endforelse
            </ul>
        </section>

        {{-- Quick actions --}}
        <section class="card self-start overflow-hidden">
            <header class="border-b border-line px-5 py-3.5"><h2 class="text-sm font-semibold text-ink">{{ __('Quick actions') }}</h2></header>
            <div class="grid grid-cols-2 gap-px bg-line">
                @foreach ([
                    ['Add employee', 'users', route('employees.create')],
                    ['Monthly attendance', 'calendar', route('attendance.index', $scope)],
                    ['Missing punch', 'clock', route('attendance.missing-punch', $scope)],
                    ['Generate salary', 'wallet', route('payroll.index')],
                    ['To Do List', 'shield', route('compliance.to-do', $scope)],
                    ['Reports', 'chart', route('reports.index', $scope)],
                ] as [$label, $icon, $href])
                    <a href="{{ $href }}" class="flex items-center gap-2.5 bg-surface px-4 py-3.5 text-sm text-ink-2 transition-colors hover:bg-primary-soft/50 hover:text-ink">
                        <x-icon :name="$icon" class="size-[18px] text-ink-3" />{{ __($label) }}
                    </a>
                @endforeach
            </div>
            <p class="border-t border-line px-5 py-2.5 text-xs text-ink-3">{{ __('Tip: press') }} <kbd class="rounded border border-line bg-sunken px-1 font-mono">Ctrl K</kbd> {{ __('to find any employee or page.') }}</p>
        </section>
    </div>

    <h2 class="mb-2.5 mt-7 text-13 font-semibold text-ink-2">{{ __('Today') }}</h2>
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ($cards as $card)
            <a href="{{ $card['href'] }}" class="group block rounded-lg border border-line bg-surface p-4 shadow-hairline transition-colors hover:border-primary/40 hover:bg-primary-soft/30">
                <div class="flex items-start justify-between gap-2">
                    <p class="flex items-center gap-2 text-13 font-medium text-ink-2"><span class="size-2 rounded-full {{ $card['accent'] }}"></span>{{ $card['label'] }}</p>
                    <x-icon name="arrow-right" class="size-4 text-ink-3 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100" />
                </div>
                <p class="mt-2 font-mono text-2xl font-medium tabular text-ink">{{ number_format($card['value']) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-7 grid grid-cols-1 gap-5 xl:grid-cols-3">
        {{-- Daily attendance statistic (department wise) --}}
        <section class="card xl:col-span-2" x-data="{ table: false }">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-3.5">
                <div>
                    <h2 class="text-sm font-semibold text-ink">{{ __('Daily attendance statistic (department wise)') }}</h2>
                    <p class="text-xs text-ink-3">{{ $today->format('l, d M Y') }}</p>
                </div>
                <button type="button" x-on:click="table = ! table" class="text-xs font-medium text-primary hover:underline" x-text="table ? @js(__('Show chart')) : @js(__('Show table'))"></button>
            </div>

            @if ($departments->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-ink-3">{{ __('No employees yet.') }}</p>
            @else
                <div class="px-5 py-4" x-show="! table">
                    <ul class="mb-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-2" aria-label="{{ __('Legend') }}">
                        @foreach ($segments as $segment)
                            <li class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm {{ $segment['class'] }}"></span>{{ $segment['label'] }}</li>
                        @endforeach
                    </ul>
                    <ul class="space-y-3">
                        @foreach ($departments as $name => $counts)
                            @php $total = array_sum($counts); @endphp
                            <li>
                                <div class="mb-1 flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                    <span class="font-medium text-ink">{{ $name }}</span>
                                    <span class="text-xs text-ink-2">
                                        {{ collect($segments)->map(fn ($s, $key) => $counts[$key] ? $counts[$key].' '.mb_strtolower($s['label']) : null)->filter()->implode(' · ') }}
                                    </span>
                                </div>
                                <div class="flex h-3 gap-0.5 overflow-hidden rounded" role="img"
                                     aria-label="{{ $name }}: {{ collect($segments)->map(fn ($s, $key) => $counts[$key].' '.$s['label'])->implode(', ') }}">
                                    @foreach ($segments as $key => $segment)
                                        @if ($counts[$key])
                                            <span class="{{ $segment['class'] }} h-full first:rounded-l last:rounded-r" style="width: {{ $counts[$key] / $total * 100 }}%"
                                                  title="{{ $segment['label'] }}: {{ $counts[$key] }} ({{ round($counts[$key] / $total * 100) }}%)"></span>
                                        @endif
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="overflow-x-auto" x-show="table" style="display: none;">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left font-semibold text-ink-2">
                            <tr>
                                <th class="px-5 py-2">{{ __('Department') }}</th>
                                @foreach ($segments as $segment)<th class="px-3 py-2 text-right">{{ $segment['label'] }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($departments as $name => $counts)
                                <tr>
                                    <td class="px-5 py-2 text-ink">{{ $name }}</td>
                                    @foreach (array_keys($segments) as $key)<td class="px-3 py-2 text-right">{{ $counts[$key] }}</td>@endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Requests --}}
        <section class="card" x-data="{ tab: 'attendance' }">
            <div class="flex items-center justify-between gap-2 border-b border-line px-5 py-3.5">
                <h2 class="text-sm font-semibold text-ink">{{ __('Requests') }}</h2>
                <div class="flex gap-0.5 rounded-md bg-sunken p-0.5" role="tablist">
                    <button type="button" role="tab" :aria-selected="tab === 'attendance'" x-on:click="tab = 'attendance'"
                            class="rounded-[5px] px-2 py-1 text-xs font-medium" :class="tab === 'attendance' ? 'bg-surface text-ink shadow-hairline' : 'text-ink-2 hover:text-ink'">{{ __('Attendance') }}</button>
                    <button type="button" role="tab" :aria-selected="tab === 'leave'" x-on:click="tab = 'leave'"
                            class="rounded-[5px] px-2 py-1 text-xs font-medium" :class="tab === 'leave' ? 'bg-surface text-ink shadow-hairline' : 'text-ink-2 hover:text-ink'">{{ __('Leave') }}</button>
                </div>
            </div>

            <div x-show="tab === 'attendance'">
                <ul class="divide-y divide-line">
                    @forelse ($attendanceRequests as $request)
                        <li class="px-5 py-3 text-sm">
                            <p class="font-medium text-ink">{{ $request->employee->full_name }}</p>
                            <p class="text-xs text-ink-3">{{ $request->work_date->format('d M Y') }} · {{ $request->requested_in->format('H:i') }}–{{ $request->requested_out->format('H:i') }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-ink-3">{{ __('No attendance requests found.') }}</li>
                    @endforelse
                </ul>
                <a href="{{ route('attendance.requests', ['status' => 'pending']) }}" class="block border-t border-line px-5 py-2.5 text-center text-13 font-medium text-primary hover:bg-sunken/60">{{ __('See All Attendance Requests') }}</a>
            </div>

            <div x-show="tab === 'leave'" style="display: none;">
                <ul class="divide-y divide-line">
                    @forelse ($leaveRequests as $leave)
                        <li class="px-5 py-3 text-sm">
                            <p class="font-medium text-ink">{{ $leave->employee->full_name }} <span class="font-normal text-ink-3">· {{ $leave->leaveType->code }}</span></p>
                            <p class="text-xs text-ink-3">{{ $leave->start_date->format('d M') }}–{{ $leave->end_date->format('d M Y') }} · {{ trans_choice(':count day|:count days', (int) $leave->days) }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-ink-3">{{ __('No leave requests found.') }}</li>
                    @endforelse
                </ul>
                <a href="{{ route('attendance.leave', ['status' => 'pending']) }}" class="block border-t border-line px-5 py-2.5 text-center text-13 font-medium text-primary hover:bg-sunken/60">{{ __('See All Leave Requests') }}</a>
            </div>
        </section>
    </div>

    @php
        $health = $compliance['health'];
        $tone = $health >= 90 ? ['text-success', 'bg-success'] : ($health >= 60 ? ['text-warn', 'bg-warn'] : ['text-danger', 'bg-danger']);
    @endphp
    <section class="card mt-5">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-3.5">
            <h2 class="text-sm font-semibold text-ink">{{ __('Compliance Health') }}</h2>
            <a href="{{ route('compliance.to-do') }}" class="text-sm font-semibold text-primary hover:underline">{{ __('Open To Do List') }}</a>
        </div>
        <div class="grid grid-cols-1 gap-6 px-5 py-5 lg:grid-cols-3">
            <div>
                <p class="text-sm text-ink-3">{{ __('Tasks past their due date that are done') }}</p>
                <p class="mt-1 font-mono text-4xl font-medium {{ $tone[0] }}">{{ $health }}%</p>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-sunken" role="img" aria-label="{{ $health }}%"><div class="h-full rounded-full {{ $tone[1] }}" style="width: {{ $health }}%"></div></div>
                <dl class="mt-4 grid grid-cols-3 gap-2 text-center text-sm">
                    @foreach (['overdue' => [__('Overdue'), 'text-danger'], 'week' => [__('This week'), 'text-warn'], 'upcoming' => [__('Upcoming'), 'text-ink']] as $key => [$label, $color])
                        <a href="{{ route('compliance.to-do', ['status' => $key]) }}" class="rounded-md bg-sunken px-2 py-2 hover:bg-line">
                            <dd class="font-mono text-xl font-medium {{ $color }}">{{ $compliance['counts'][$key] }}</dd>
                            <dt class="text-xs text-ink-3">{{ $label }}</dt>
                        </a>
                    @endforeach
                </dl>
            </div>
            <div class="lg:col-span-2">
                <p class="text-sm font-medium text-ink-2">{{ __('Needs attention') }}</p>
                <ul class="mt-2 divide-y divide-line rounded-md ring-1 ring-line">
                    @forelse ($compliance['urgent'] as $task)
                        <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                            <div>
                                <p class="font-medium text-ink">{{ $task->title }}</p>
                                <p class="text-xs text-ink-3">{{ $task->employee ? $task->employee->full_name.' · ' : '' }}{{ $task->company->establishment_name }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-medium {{ $task->status() === 'overdue' ? 'text-danger' : 'text-warn' }}">
                                {{ $task->status() === 'overdue' ? __('Overdue since :date', ['date' => $task->due_on->format('d M')]) : __('Due :date', ['date' => $task->due_on->format('d M')]) }}
                            </span>
                        </li>
                    @empty
                        <li class="px-3 py-6 text-center text-sm text-ink-3">{{ __('Nothing overdue or due this week.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>
</x-app-layout>
