<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php
        $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
        $labels = ['A' => 'A', 'P' => 'P', 'HD' => 'HD', 'WO' => 'WO', 'PH' => 'PH', 'LEAVE' => 'LEAVE', 'COFF' => 'COFF'];
        $hours = fn (float $h) => $h > 0 ? sprintf('%d:%02d', floor($h), round(($h - floor($h)) * 60)) : '--';
    @endphp

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Employee wise attendance report') }}</h2>
            <div class="flex gap-2 print:hidden">
                @if ($employee)
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'correct-raw-data')" class="rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Attendance data correction') }}</button>
                @endif
                <button type="button" onclick="window.print()" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Print') }}</button>
            </div>
        </div>
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <form method="GET" action="{{ route('attendance.employee-report') }}" class="flex flex-wrap items-end gap-3 border-b border-gray-200 px-5 py-4 print:hidden">
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">{{ __('Date') }}</span>
                <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="{{ $select }}">
            </label>
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">{{ __('Employee') }}</span>
                <select name="employee" class="{{ $select }} min-w-72">
                    <option value="">{{ __('Select employee') }}</option>
                    @foreach ($employees as $option)
                        <option value="{{ $option->uuid }}" @selected($employee?->is($option))>{{ $option->employee_code }} - {{ $option->full_name }} ({{ $option->company?->establishment_name }})</option>
                    @endforeach
                </select>
            </label>
            <button class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Find') }}</button>
        </form>

        @if (! $employee)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Select an employee and month.') }}</p>
        @else
            <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-4">
                <div>
                    <h3 class="text-xl font-semibold text-gray-900">{{ $employee->full_name }}</h3>
                    <p class="text-sm text-gray-600">{{ __('Attendance History ( From :from To :to )', ['from' => $month->toDateString(), 'to' => $month->endOfMonth()->toDateString()]) }}</p>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-0.5 text-sm">
                    <dt class="text-gray-500">{{ __('Position name') }}</dt><dd>: {{ $employee->position?->name ?? '—' }}</dd>
                    <dt class="text-gray-500">{{ __('Department name') }}</dt><dd>: {{ $employee->subDepartment?->name ?? $employee->department?->name ?? '—' }}</dd>
                    <dt class="text-gray-500">{{ __('Employee id') }}</dt><dd>: {{ $employee->employee_code }}</dd>
                    <dt class="text-gray-500">{{ __('Shift') }}</dt><dd>: {{ $employee->shift?->name ?? '—' }}</dd>
                </dl>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                        <tr>
                            <th class="px-4 py-3">{{ __('Sl') }}</th>
                            <th class="px-4 py-3">{{ __('Date') }}</th>
                            <th class="px-4 py-3">{{ __('Day') }}</th>
                            <th class="px-4 py-3">{{ __('In time') }}</th>
                            <th class="px-4 py-3">{{ __('Out time') }}</th>
                            <th class="px-4 py-3">{{ __('Total worked hour') }}</th>
                            <th class="px-4 py-3">{{ __('Total over time') }}</th>
                            <th class="px-4 py-3">{{ __('Attendance status') }}</th>
                            <th class="px-4 py-3">{{ __('Original shift') }}</th>
                            <th class="px-4 py-3">{{ __('Actual shift') }}</th>
                            <th class="px-4 py-3">{{ __('Lock') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($report['days'] as $date => $d)
                            <tr @class(['hover:bg-gray-50', 'text-gray-400' => ! $d['status']])>
                                <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2 whitespace-nowrap">{{ $date }}</td>
                                <td class="px-4 py-2">{{ $d['date']->format('l') }}</td>
                                <td class="px-4 py-2">{{ $d['in']?->format('H:i') }}</td>
                                <td class="px-4 py-2">{{ $d['out']?->format('H:i') }}</td>
                                <td class="px-4 py-2">{{ $hours($d['hours']) }}</td>
                                <td class="px-4 py-2">{{ $hours($d['ot']) }}</td>
                                <td class="px-4 py-2 font-semibold">
                                    {{ $d['status'] ? ($d['missing'] && $d['status'] === 'A' ? 'MP' : $labels[$d['status']]) : '-' }}
                                    @if ($d['late'])<span class="text-xs font-normal text-violet-700">({{ __('late') }})</span>@endif
                                    @if ($d['holiday'])<span class="text-xs font-normal text-info">{{ $d['holiday'] }}</span>@endif
                                </td>
                                <td class="px-4 py-2">{{ $d['shift'] }}</td>
                                <td class="px-4 py-2 font-mono">{{ $d['code'] }}</td>
                                <td class="px-4 py-2">{{ $d['locked'] ? __('Locked') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 text-sm font-semibold">
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right">{{ __('Total') }}</td>
                            <td class="px-4 py-2">{{ $hours($report['totals']['hours']) }}</td>
                            <td class="px-4 py-2">{{ $hours($report['totals']['ot']) }}</td>
                            <td colspan="4" class="px-4 py-2 font-normal">
                                @foreach (['P' => 'P', 'A' => 'LOP', 'HD' => 'HD', 'WO' => 'WO', 'PH' => 'PH', 'LEAVE' => 'LEAVE', 'COFF' => 'COFF'] as $key => $label)
                                    <span class="me-3">{{ $label }}: <strong>{{ $report['totals'][$key] + 0 }}</strong></span>
                                @endforeach
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @include('attendance._correction-modal', ['employee' => $employee, 'from' => $month, 'to' => $month->endOfMonth()])
        @endif
    </div>
    </div>
</x-app-layout>
