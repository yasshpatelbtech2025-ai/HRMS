<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $money = fn ($n) => number_format((float) $n, 2);
        $select = 'control-inline';
        $exportQuery = ['company' => $company?->uuid, 'fy' => $fy];
    @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('TDS (Income Tax)') }}</h1>
                <p class="text-sm text-gray-500">{{ __('Financial year April :from – March :to', ['from' => $fy, 'to' => $fy + 1]) }}</p>
            </div>
            <form method="GET" action="{{ route('payroll.tds') }}" class="flex flex-wrap items-center gap-2">
                <label class="flex items-center gap-2"><span class="text-13 font-medium text-ink-2">{{ __('Company') }}</span><select name="company" onchange="this.form.submit()" class="{{ $select }}">
                    @foreach ($companies as $option)<option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>@endforeach
                </select></label>
                <select name="fy" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Financial year') }}">
                    @foreach (range((int) date('Y'), 2020) as $year)<option value="{{ $year }}" @selected($fy === $year)>{{ $year }} - {{ $year + 1 }}</option>@endforeach
                </select>
                @if ($company)
                    <a href="{{ route('payroll.tds.export', ['format' => 'xlsx'] + $exportQuery) }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Excel') }}</a>
                    <a href="{{ route('payroll.tds.export', ['format' => 'csv'] + $exportQuery) }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('CSV') }}</a>
                @endif
            </form>
        </div>
        <x-stat-strip :items="[
            ['label' => __('Employees'), 'value' => $rows->count(), 'hint' => __('FY :fy', ['fy' => $fy.'-'.substr($fy + 1, 2)])],
            ['label' => __('Tax for the year'), 'value' => '₹'.number_format($rows->sum('tax'))],
            ['label' => __('Deducted so far'), 'value' => '₹'.number_format($rows->sum('paid_tds')), 'tone' => 'success'],
            ['label' => __('Still to deduct'), 'value' => '₹'.number_format($rows->sum('balance')), 'tone' => $rows->sum('balance') > 0 ? 'warn' : null],
        ]" />

        <div class="card">

        @if ($company && ! $deducting)
            <div class="mx-5 mt-4 rounded-md bg-warn-soft px-4 py-3 text-sm text-warn ring-1 ring-amber-200">
                {{ __(':company does not deduct TDS from salary, so payroll leaves it out. Turn on "Employee Tax Deduction" in', ['company' => $company->establishment_name]) }}
                <a href="{{ route('configs.payroll', ['company' => $company->uuid]) }}" class="font-semibold underline">{{ __('Configs › Payroll') }}</a>
                {{ __('to deduct the monthly TDS below.') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="px-3 py-3">{{ __('Employee') }}</th>
                        <th class="px-3 py-3">{{ __('Regime') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Projected gross') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Taxable') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Tax for the year') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('TDS deducted') }}</th>
                        <th class="px-3 py-3 text-right">{{ __('Balance') }}</th>
                        <th class="px-3 py-3">{{ __('Monthly TDS') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($rows as $r)
                        @php $e = $r['employee']; @endphp
                        <tr class="align-top hover:bg-gray-50">
                            <td class="px-3 py-3">
                                <p class="font-medium text-gray-900">{{ $e->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $e->employee_code }}{{ $e->pan_card_no ? ' · PAN '.$e->pan_card_no : ' · '.__('no PAN') }}</p>
                            </td>
                            <td class="px-3 py-3">{{ ucfirst($r['regime']) }}</td>
                            <td class="px-3 py-3 text-right" title="{{ __(':months month(s) left at :monthly a month', ['months' => $r['months_left'], 'monthly' => $money($r['monthly_gross'])]) }}">
                                {{ $money($r['gross']) }}
                                <p class="text-xs text-gray-500">{{ __('paid so far :amount', ['amount' => $money($r['paid_gross'])]) }}</p>
                                @if ($r['bonus'] > 0)<p class="text-xs text-gray-500">{{ __('incl. bonus :amount', ['amount' => $money($r['bonus'])]) }}</p>@endif
                            </td>
                            <td class="px-3 py-3 text-right">{{ $money($r['taxable']) }}<p class="text-xs text-gray-500">{{ __('after :amount deductions', ['amount' => $money($r['standard'] + $r['other'])]) }}</p></td>
                            <td class="px-3 py-3 text-right font-semibold">{{ $money($r['tax']) }}</td>
                            <td class="px-3 py-3 text-right">{{ $money($r['paid_tds']) }}</td>
                            <td class="px-3 py-3 text-right">{{ $money($r['balance']) }}</td>
                            <td class="px-3 py-3">
                                <form method="POST" action="{{ route('payroll.tds.override', $e) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="fy" value="{{ $fy }}">
                                    <input type="number" name="monthly_amount" step="0.01" min="0" value="{{ $r['override']?->monthly_amount }}" placeholder="{{ $money($r['estimate']) }}"
                                           class="{{ $select }} w-28 py-1" aria-label="{{ __('Monthly TDS for :name', ['name' => $e->full_name]) }}">
                                    <button class="rounded-md border border-line-strong bg-surface text-ink shadow-hairline hover:bg-sunken px-2 py-1 text-xs font-medium">{{ __('Set') }}</button>
                                </form>
                                <p class="mt-1 text-xs {{ $r['override'] ? 'font-medium text-brand-700' : 'text-gray-500' }}">
                                    {{ $r['override'] ? __('Set by hand (estimate :amount) — clear and Set to estimate again', ['amount' => $money($r['estimate'])]) : __('Estimated · :months month(s) left', ['months' => $r['months_left']]) }}
                                </p>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="8" icon="inbox">{{ $company ? __('No employees for this year.') : __('Add a company first.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="border-t border-gray-100 px-5 py-3 text-xs text-gray-500">
            {{ __('How it works: the year\'s gross is what was paid so far plus the monthly gross for the months left and any yearly bonus. Less the standard deduction (₹75,000 new regime, ₹50,000 old; the old regime also allows PT and employee PF under 80C up to ₹1.5 lakh), tax is worked out on the regime\'s slabs with the 87A rebate (no tax up to ₹12 lakh in the new regime) and 4% cess. The tax not yet deducted is spread over the months left and deducted in payroll each month. Set a monthly amount to use instead, e.g. after investment proofs. Tax regime is set on each employee.') }}
        </p>
    </div>
    </div>
</x-app-layout>
