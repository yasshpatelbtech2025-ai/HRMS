<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>

    @php
        $cards = [
            ['label' => __('Total employee'), 'value' => $stats['total'], 'accent' => 'bg-indigo-500', 'href' => route('employees.index')],
            ['label' => __('Today presents'), 'value' => $stats['present'], 'accent' => 'bg-[#0ca30c]', 'href' => route('attendance.index')],
            ['label' => __('Today absents'), 'value' => $stats['absent'], 'accent' => 'bg-[#d03b3b]', 'href' => route('attendance.index')],
            ['label' => __('Today leave'), 'value' => $stats['leave'], 'accent' => 'bg-[#fab219]', 'href' => route('attendance.leave')],
        ];
        // Status colours (good / warning / critical) with a neutral for days off; every bar also carries its counts as text.
        $segments = [
            'present' => ['label' => __('Present'), 'class' => 'bg-[#0ca30c]'],
            'leave' => ['label' => __('Leave'), 'class' => 'bg-[#fab219]'],
            'absent' => ['label' => __('Absent'), 'class' => 'bg-[#d03b3b]'],
            'off' => ['label' => __('Week off / holiday'), 'class' => 'bg-gray-300'],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <a href="{{ $card['href'] }}" class="relative overflow-hidden rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 transition hover:ring-gray-300">
                <span class="absolute inset-y-0 start-0 w-1 {{ $card['accent'] }}"></span>
                <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900">{{ $card['value'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Daily attendance statistic (department wise) --}}
        <section class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200 xl:col-span-2" x-data="{ table: false }">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">{{ __('Daily attendance statistic (department wise)') }}</h2>
                    <p class="text-xs text-gray-500">{{ $today->format('l, d M Y') }}</p>
                </div>
                <button type="button" x-on:click="table = ! table" class="text-xs font-medium text-indigo-600 hover:underline" x-text="table ? @js(__('Show chart')) : @js(__('Show table'))"></button>
            </div>

            @if ($departments->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('No employees yet.') }}</p>
            @else
                <div class="px-5 py-4" x-show="! table">
                    <ul class="mb-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-600" aria-label="{{ __('Legend') }}">
                        @foreach ($segments as $segment)
                            <li class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm {{ $segment['class'] }}"></span>{{ $segment['label'] }}</li>
                        @endforeach
                    </ul>
                    <ul class="space-y-3">
                        @foreach ($departments as $name => $counts)
                            @php $total = array_sum($counts); @endphp
                            <li>
                                <div class="mb-1 flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                    <span class="font-medium text-gray-900">{{ $name }}</span>
                                    <span class="text-xs text-gray-600">
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
                        <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                            <tr>
                                <th class="px-5 py-2">{{ __('Department') }}</th>
                                @foreach ($segments as $segment)<th class="px-3 py-2 text-right">{{ $segment['label'] }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($departments as $name => $counts)
                                <tr>
                                    <td class="px-5 py-2 text-gray-900">{{ $name }}</td>
                                    @foreach (array_keys($segments) as $key)<td class="px-3 py-2 text-right">{{ $counts[$key] }}</td>@endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Requests --}}
        <section class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200" x-data="{ tab: 'attendance' }">
            <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-5 py-4">
                <h2 class="text-base font-semibold text-gray-900">{{ __('Requests') }}</h2>
                <div class="flex gap-1" role="tablist">
                    <button type="button" role="tab" :aria-selected="tab === 'attendance'" x-on:click="tab = 'attendance'"
                            class="rounded-md border px-2 py-1 text-xs font-medium" :class="tab === 'attendance' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-indigo-300 text-indigo-700'">{{ __('Attendance') }}</button>
                    <button type="button" role="tab" :aria-selected="tab === 'leave'" x-on:click="tab = 'leave'"
                            class="rounded-md border px-2 py-1 text-xs font-medium" :class="tab === 'leave' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-indigo-300 text-indigo-700'">{{ __('Leave') }}</button>
                </div>
            </div>

            <div x-show="tab === 'attendance'">
                <ul class="divide-y divide-gray-100">
                    @forelse ($attendanceRequests as $request)
                        <li class="px-5 py-3 text-sm">
                            <p class="font-medium text-gray-900">{{ $request->employee->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $request->work_date->format('d M Y') }} · {{ $request->requested_in->format('H:i') }}–{{ $request->requested_out->format('H:i') }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-gray-500">{{ __('No attendance requests found.') }}</li>
                    @endforelse
                </ul>
                <a href="{{ route('attendance.requests', ['status' => 'pending']) }}" class="block border-t border-gray-100 px-5 py-3 text-center text-sm font-semibold text-indigo-600 hover:bg-gray-50">{{ __('See All Attendance Requests') }}</a>
            </div>

            <div x-show="tab === 'leave'" style="display: none;">
                <ul class="divide-y divide-gray-100">
                    @forelse ($leaveRequests as $leave)
                        <li class="px-5 py-3 text-sm">
                            <p class="font-medium text-gray-900">{{ $leave->employee->full_name }} <span class="font-normal text-gray-500">· {{ $leave->leaveType->code }}</span></p>
                            <p class="text-xs text-gray-500">{{ $leave->start_date->format('d M') }}–{{ $leave->end_date->format('d M Y') }} · {{ trans_choice(':count day|:count days', (int) $leave->days) }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-gray-500">{{ __('No leave requests found.') }}</li>
                    @endforelse
                </ul>
                <a href="{{ route('attendance.leave', ['status' => 'pending']) }}" class="block border-t border-gray-100 px-5 py-3 text-center text-sm font-semibold text-indigo-600 hover:bg-gray-50">{{ __('See All Leave Requests') }}</a>
            </div>
        </section>
    </div>

    <section class="mt-6 rounded-lg border border-dashed border-gray-300 bg-white px-5 py-6 text-sm text-gray-500">
        <h2 class="text-base font-semibold text-gray-900">{{ __('Compliance Health') }}</h2>
        <p class="mt-1">{{ __('Compliance health, pending and overdue compliances and the timeline appear here once the Compliance module is built.') }}</p>
    </section>
</x-app-layout>
