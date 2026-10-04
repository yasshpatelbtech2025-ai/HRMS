<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php $money = fn ($n) => number_format((float) $n, 2); @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('payroll.index') }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to salary list') }}</a>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('payroll.chart.export', [$sheet, 'format' => 'csv']) }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('CSV') }}</a>
            <a href="{{ route('payroll.chart.export', $sheet) }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Excel') }}</a>
            <button type="button" onclick="window.print()" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Print') }}</button>
            @if ($approval)
                @if ($sheet->isApproved())
                    <form method="POST" action="{{ route('payroll.unapprove', $sheet) }}" data-confirm="{{ __('Undo the approval? Advance recoveries and loan installments recorded by it are reversed.') }}">
                        @csrf
                        @method('PUT')
                        <button class="rounded-md bg-amber-500 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-600">{{ __('Undo approval') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('payroll.approve', $sheet) }}" data-confirm="{{ __('Approve this salary? Advance recoveries and loan installments on it will be recorded.') }}">
                        @csrf
                        @method('PUT')
                        <button class="rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Approve salary') }}</button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $approval ? __('Salary Approval') : __('Employee Salary Chart') }}</h2>
                <p class="text-sm text-gray-500">{{ $sheet->company->establishment_name }} · {{ $sheet->month->format('F Y') }}</p>
            </div>
            <span class="rounded-full px-3 py-1 text-sm font-medium {{ $sheet->isApproved() ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $sheet->isApproved() ? __('Approved :date', ['date' => $sheet->approved_at->format('d-m-Y')]) : __('Not approved') }}
            </span>
        </div>

        @if ($chart['rows']->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('No payslips on this sheet.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50 text-center font-semibold text-gray-700">
                        <tr>
                            <th rowspan="2" class="px-2 py-2 text-left">{{ __('Sl') }}</th>
                            <th rowspan="2" class="px-2 py-2 text-left">{{ __('Employee') }}</th>
                            <th rowspan="2" class="px-2 py-2">{{ __('Paid days') }}</th>
                            <th rowspan="2" class="px-2 py-2">{{ __('LOP') }}</th>
                            <th rowspan="2" class="px-2 py-2">{{ __('OT hrs') }}</th>
                            <th colspan="{{ count($chart['earnings']) + 1 }}" class="border-l border-gray-200 bg-green-50 px-2 py-1">{{ __('Earnings') }}</th>
                            <th colspan="{{ count($chart['deductions']) + 1 }}" class="border-l border-gray-200 bg-red-50 px-2 py-1">{{ __('Deductions') }}</th>
                            <th rowspan="2" class="border-l border-gray-200 bg-gray-100 px-2 py-2">{{ __('Net pay') }}</th>
                            @if ($chart['employer'])
                                <th colspan="{{ count($chart['employer']) }}" class="border-l border-gray-200 px-2 py-1">{{ __('Employer contribution') }}</th>
                            @endif
                            <th rowspan="2" class="px-2 py-2 print:hidden"><span class="sr-only">{{ __('Payslip') }}</span></th>
                        </tr>
                        <tr>
                            @foreach ($chart['earnings'] as $label)<th class="border-l border-gray-100 bg-green-50 px-2 py-1 font-medium">{{ $label }}</th>@endforeach
                            <th class="bg-green-100 px-2 py-1">{{ __('Gross') }}</th>
                            @foreach ($chart['deductions'] as $label)<th class="border-l border-gray-100 bg-red-50 px-2 py-1 font-medium">{{ $label }}</th>@endforeach
                            <th class="bg-red-100 px-2 py-1">{{ __('Total') }}</th>
                            @foreach ($chart['employer'] as $label)<th class="border-l border-gray-100 px-2 py-1 font-medium">{{ $label }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-right">
                        @foreach ($chart['rows'] as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-2 text-left text-gray-500">{{ $loop->iteration }}</td>
                                <td class="whitespace-nowrap px-2 py-2 text-left"><span class="font-medium text-gray-900">{{ $row['name'] }}</span> <span class="text-gray-500">{{ $row['code'] }}</span></td>
                                <td class="px-2 py-2">{{ $row['paid_days'] + 0 }}</td>
                                <td class="px-2 py-2">{{ $row['lop'] + 0 }}</td>
                                <td class="px-2 py-2">{{ $row['ot'] + 0 }}</td>
                                @foreach ($chart['earnings'] as $label)<td class="px-2 py-2">{{ isset($row['earnings'][$label]) ? $money($row['earnings'][$label]) : '-' }}</td>@endforeach
                                <td class="bg-green-50 px-2 py-2 font-semibold">{{ $money($row['gross']) }}</td>
                                @foreach ($chart['deductions'] as $label)<td class="px-2 py-2">{{ isset($row['deductions'][$label]) ? $money($row['deductions'][$label]) : '-' }}</td>@endforeach
                                <td class="bg-red-50 px-2 py-2 font-semibold">{{ $money($row['total_deductions']) }}</td>
                                <td class="bg-gray-50 px-2 py-2 font-bold text-gray-900">{{ $money($row['net']) }}</td>
                                @foreach ($chart['employer'] as $label)<td class="px-2 py-2">{{ isset($row['employer'][$label]) ? $money($row['employer'][$label]) : '-' }}</td>@endforeach
                                <td class="px-2 py-2 print:hidden"><a href="{{ route('payroll.payslips.show', $row['uuid']) }}" class="text-brand-600 hover:underline">{{ __('Payslip') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50 text-right font-semibold">
                        <tr>
                            <td colspan="{{ 5 + count($chart['earnings']) }}" class="px-2 py-2">{{ __('Total') }}</td>
                            <td class="px-2 py-2">{{ $money($chart['totals']['gross']) }}</td>
                            <td colspan="{{ count($chart['deductions']) }}"></td>
                            <td class="px-2 py-2">{{ $money($chart['totals']['total_deductions']) }}</td>
                            <td class="px-2 py-2">{{ $money($chart['totals']['net']) }}</td>
                            <td colspan="{{ count($chart['employer']) + 1 }}"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
