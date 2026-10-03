<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $money = fn ($n) => number_format((float) $n, 2);
        $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
        $exportQuery = ['company' => $company?->uuid, 'fy' => $fy];
    @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('TDS (Income Tax)') }}</h2>
                <p class="text-sm text-gray-500">{{ __('Financial year April :from – March :to', ['from' => $fy, 'to' => $fy + 1]) }}</p>
            </div>
            <form method="GET" action="{{ route('payroll.tds') }}" class="flex flex-wrap items-center gap-2">
                <select name="company" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Company') }}">
                    @foreach ($companies as $option)<option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>@endforeach
                </select>
                <select name="fy" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Financial year') }}">
                    @foreach (range((int) date('Y'), 2020) as $year)<option value="{{ $year }}" @selected($fy === $year)>{{ $year }} - {{ $year + 1 }}</option>@endforeach
                </select>
                @if ($company)
                    <a href="{{ route('payroll.tds.export', ['format' => 'xlsx'] + $exportQuery) }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Excel') }}</a>
                    <a href="{{ route('payroll.tds.export', ['format' => 'csv'] + $exportQuery) }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('CSV') }}</a>
                @endif
            </form>
        </div>

        @if ($company && ! $deducting)
            <div class="mx-5 mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200">
                {{ __(':company does not deduct TDS from salary, so payroll leaves it out. Turn on "Employee Tax Deduction" in', ['company' => $company->establishment_name]) }}
                <a href="{{ route('configs.payroll', ['company' => $company->uuid]) }}" class="font-semibold underline">{{ __('Configs › Payroll') }}</a>
                {{ __('to deduct the monthly TDS below.') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
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
                <tbody class="divide-y divide-gray-100">
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
                                    <button class="rounded-md bg-gray-800 px-2 py-1 text-xs font-semibold text-white hover:bg-gray-900">{{ __('Set') }}</button>
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
</x-app-layout>
