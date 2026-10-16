<x-app-layout>
    <x-slot name="header">{{ __('Reports') }}</x-slot>

    <x-section-tabs section="reports" />

    @php $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500'; @endphp

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('Monthly attendance sheet') }}</h2>
                <p class="hidden text-sm text-gray-600 print:block">{{ $company?->establishment_name }} · {{ $month->format('F Y') }}</p>
            </div>
            @include('reports._exports')
        </div>
        <x-stat-strip :items="[
            ['label' => __('Employees'), 'value' => count($employees)],
        ]" />

        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap gap-3 border-b border-gray-200 px-5 py-4 print:hidden">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Month') }}">
            <select name="company" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Company') }}">
                @foreach ($companies as $option)
                    <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                @endforeach
            </select>
        </form>

        @if ($employees->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ $company ? __('No employees in :month.', ['month' => $month->format('F Y')]) : __('Add a company first.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-xs">
                    <thead class="bg-gray-50 text-center font-semibold text-gray-700">
                        <tr>
                            <th rowspan="2" class="border border-gray-200 px-2 py-1 text-left">{{ __('ID') }}</th>
                            <th rowspan="2" class="border border-gray-200 px-2 py-1 text-left">{{ __('Employee Name') }}</th>
                            <th rowspan="2" class="border border-gray-200 px-2 py-1">{{ __('Time') }}</th>
                            <th colspan="{{ $month->daysInMonth }}" class="border border-gray-200 px-2 py-1">{{ __('Attendance') }}</th>
                        </tr>
                        <tr>
                            @foreach (range(1, $month->daysInMonth) as $day)
                                <th class="border border-gray-200 px-1 py-1 {{ $month->setDay($day)->isSunday() ? 'text-warn' : '' }}">{{ sprintf('%02d', $day) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            @php $days = $grid[$employee->id]['days']; @endphp
                            <tr>
                                <td rowspan="2" class="border border-gray-200 px-2 py-1 font-mono">{{ $employee->employee_code }}</td>
                                <td rowspan="2" class="whitespace-nowrap border border-gray-200 px-2 py-1 font-medium text-gray-900">{{ $employee->full_name }}</td>
                                <td class="whitespace-nowrap border border-gray-200 px-2 py-1 text-gray-600">{{ __('In Time') }}</td>
                                @foreach ($days as $d)
                                    <td class="border border-gray-200 px-1 py-1 text-center {{ in_array($d['status'], ['WO', 'PH', 'LEAVE', 'COFF'], true) ? 'text-gray-500' : '' }}">
                                        {{ $d['in']?->format('H:i') ?? (in_array($d['status'], ['WO', 'PH', 'LEAVE', 'COFF'], true) ? $d['status'] : '') }}
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="whitespace-nowrap border border-gray-200 px-2 py-1 text-gray-600">{{ __('Out Time') }}</td>
                                @foreach ($days as $d)
                                    <td class="border border-gray-200 px-1 py-1 text-center">{{ $d['out']?->format('H:i') }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="px-5 py-3 text-xs text-gray-500 print:hidden">{{ __('Blank in-time cells are absent or not punched; WO, PH, LEAVE and COFF are shown where there was no punch.') }}</p>
        @endif
    </div>
    </div>
</x-app-layout>
