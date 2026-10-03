<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $p = $payslip;
        $e = $p->employee;
        $a = $p->attendance;
        $money = fn ($n) => number_format((float) $n, 2);
        $masked = fn (?string $v) => $v ? str_repeat('•', max(0, strlen($v) - 4)).substr($v, -4) : '—';
        $lines = max(count($p->earnings), count($p->deductions));
    @endphp

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route('payroll.salaries', ['salary_month' => $p->month->format('Y-m')]) }}" class="text-sm text-gray-600 hover:text-gray-900">← {{ __('Back to employee salary') }}</a>
        <div class="flex gap-2">
            @unless ($p->sheet->isApproved())
                <form method="POST" action="{{ route('payroll.payslips.regenerate', $p) }}">
                    @csrf
                    @method('PUT')
                    <button class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Regenerate') }}</button>
                </form>
            @endunless
            <button type="button" onclick="window.print()" class="rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Download pay slip') }}</button>
        </div>
    </div>

    <article class="mx-auto max-w-3xl rounded-lg bg-white p-6 text-sm shadow-sm ring-1 ring-gray-200 print:max-w-none print:p-0 print:shadow-none print:ring-0">
        <header class="border-b border-gray-300 pb-3 text-center">
            <h1 class="text-xl font-bold uppercase text-gray-900">{{ $p->company->establishment_name }}</h1>
            @if ($p->company->address_line_1 || $p->company->district)
                <p class="text-xs text-gray-600">{{ collect([$p->company->address_line_1, $p->company->address_line_2, $p->company->village, $p->company->district, $p->company->state])->filter()->implode(', ') }}</p>
            @endif
            <p class="mt-2 font-semibold text-gray-800">{{ __('Pay slip for the month of :month', ['month' => $p->month->format('F Y')]) }}</p>
            @unless ($p->sheet->isApproved())<p class="text-xs font-medium text-amber-700">{{ __('Draft — salary not yet approved') }}</p>@endunless
        </header>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-1 border-b border-gray-300 py-3">
            @foreach ([
                __('Employee name') => $e->full_name, __('Employee id') => $e->employee_code,
                __('Designation') => $e->position?->name, __('Department') => $e->subDepartment?->name ?? $e->department?->name,
                __('Date of joining') => $e->date_of_joining?->format('d-m-Y'), __('Salary type') => config('employee.pay_frequencies')[$p->pay_frequency] ?? '',
                __('UAN') => $e->uan_code, __('ESIC IP no') => $e->ip_number,
                __('Bank') => $e->bank_name, __('Account no') => $masked($e->account_number),
            ] as $label => $value)
                <div class="flex gap-2"><dt class="w-32 shrink-0 text-gray-600">{{ $label }}</dt><dd class="font-medium text-gray-900">{{ filled($value) ? $value : '—' }}</dd></div>
            @endforeach
        </dl>

        <div class="grid grid-cols-3 gap-2 border-b border-gray-300 py-3 text-center text-xs sm:grid-cols-6">
            @foreach ([
                __('Days in month') => $a['days_in_month'], __('Paid days') => $a['paid_days'], __('Present') => $a['present'] + $a['half_days'] / 2,
                __('LOP') => $a['lop'] + $a['unpaid_leave'], __('WO / PH') => $a['week_offs'] + $a['holidays'], __('OT hours') => $a['ot_hours'],
            ] as $label => $value)
                <div><div class="text-gray-500">{{ $label }}</div><div class="text-base font-semibold text-gray-900">{{ $value + 0 }}</div></div>
            @endforeach
        </div>

        <table class="mt-3 w-full border border-gray-300">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="border-b border-gray-300 px-3 py-2">{{ __('Earnings') }}</th>
                    <th class="border-b border-gray-300 px-3 py-2 text-right">{{ __('Amount') }}</th>
                    <th class="border-b border-l border-gray-300 px-3 py-2">{{ __('Deductions') }}</th>
                    <th class="border-b border-gray-300 px-3 py-2 text-right">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < $lines; $i++)
                    <tr>
                        <td class="px-3 py-1">{{ $p->earnings[$i]['label'] ?? '' }}</td>
                        <td class="px-3 py-1 text-right">{{ isset($p->earnings[$i]) ? $money($p->earnings[$i]['amount']) : '' }}</td>
                        <td class="border-l border-gray-300 px-3 py-1">{{ $p->deductions[$i]['label'] ?? '' }}</td>
                        <td class="px-3 py-1 text-right">{{ isset($p->deductions[$i]) ? $money($p->deductions[$i]['amount']) : '' }}</td>
                    </tr>
                @endfor
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="border-t border-gray-300 px-3 py-2">{{ __('Gross earnings') }}</td>
                    <td class="border-t border-gray-300 px-3 py-2 text-right">{{ $money($p->gross_earnings) }}</td>
                    <td class="border-l border-t border-gray-300 px-3 py-2">{{ __('Total deductions') }}</td>
                    <td class="border-t border-gray-300 px-3 py-2 text-right">{{ $money($p->total_deductions) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-3 flex flex-wrap items-baseline justify-between gap-2 rounded-md bg-gray-100 px-4 py-3">
            <span class="text-base font-bold text-gray-900">{{ __('Net pay') }}: ₹ {{ $money($p->net_pay) }}</span>
            <span class="text-xs text-gray-700">{{ \App\Support\IndianNumber::rupeesInWords((float) $p->net_pay) }}</span>
        </div>

        @if ($p->employer)
            <p class="mt-3 text-xs text-gray-600">
                {{ __('Employer contribution') }}:
                {{ collect($p->employer)->map(fn ($l) => $l['label'].' ₹'.$money($l['amount']))->implode(' · ') }}
            </p>
        @endif

        <p class="mt-6 text-center text-xs text-gray-500">{{ __('This is a computer-generated pay slip.') }}</p>
    </article>

    @if ($print)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</x-app-layout>
