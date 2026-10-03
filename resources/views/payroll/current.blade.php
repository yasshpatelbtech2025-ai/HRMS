<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $money = fn ($n) => (float) $n > 0 ? number_format((float) $n, 2) : '-';
        $types = config('employee.pay_frequencies');
    @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Current Salary List') }}</h2>
            <form method="GET" action="{{ route('payroll.current') }}">
                <select name="company" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" aria-label="{{ __('Company') }}">
                    @foreach ($companies as $option)
                        <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if (! $employees)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Add a company first.') }}</p>
        @else
            <x-list-toolbar :action="route('payroll.current')" :per-page="$perPage" allow-all print :exports="[
                __('CSV') => route('payroll.current.export', ['format' => 'csv', 'company' => $company->uuid]),
                __('Excel') => route('payroll.current.export', ['format' => 'xlsx', 'company' => $company->uuid]),
            ]" />

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                        <tr>
                            @foreach (['Card No', 'Employee Name', 'Designation', 'Salary Type', 'Daily Rate', 'Basic', 'HRA', 'Conveyance', 'Bonus', 'CTC'] as $heading)
                                <th class="whitespace-nowrap px-4 py-3">{{ __($heading) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($employees as $employee)
                            @php
                                $elements = $employee->salaryElements->keyBy('element');
                                $ctc = $revisions->yearlyGross($elements->map(fn ($e) => ['yearly' => $e->yearly])->all());
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono">{{ $employee->employee_code }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $employee->full_name }}</td>
                                <td class="px-4 py-3">{{ $employee->position?->name }}</td>
                                <td class="px-4 py-3">{{ $types[$employee->pay_frequency] ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $money($employee->daily_rate ?? ($employee->hourly_rate ? $employee->hourly_rate * ($employee->daily_hour ?: 8) : 0)) }}</td>
                                <td class="px-4 py-3">{{ $money($elements['basic']->monthly ?? 0) }}</td>
                                <td class="px-4 py-3">{{ $money($elements['hra']->monthly ?? 0) }}</td>
                                <td class="px-4 py-3">{{ $money($elements['conveyance']->monthly ?? 0) }}</td>
                                <td class="px-4 py-3">{{ isset($elements['bonus']) ? rtrim(rtrim($elements['bonus']->monthly, '0'), '.').'%' : '-' }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $money($ctc) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-5 py-10 text-center text-gray-500">{{ __('No current employees.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-list-footer :paginator="$employees" />
        @endif
    </div>
</x-app-layout>
