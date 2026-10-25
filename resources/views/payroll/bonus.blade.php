<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $money = fn ($n) => number_format((float) $n, 2);
        $select = 'control-inline';
        $approved = $sheet?->isApproved();
    @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div>
                <h1 class="text-2xl font-semibold text-ink">{{ __('Yearly Bonus') }}</h1>
                <p class="text-sm text-gray-500">{{ __('Accounting year April :from – March :to', ['from' => $fy, 'to' => $fy + 1]) }}</p>
            </div>
            <form method="GET" action="{{ route('payroll.bonus') }}" class="flex flex-wrap items-center gap-2">
                <select name="company" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Company') }}">
                    @foreach ($companies as $option)<option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>@endforeach
                </select>
                <select name="fy" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Accounting year') }}">
                    @foreach (range((int) date('Y'), 2020) as $year)<option value="{{ $year }}" @selected($fy === $year)>{{ $year }} - {{ $year + 1 }}</option>@endforeach
                </select>
            </form>
        </div>
        @if ($sheet)
            <x-stat-strip :items="[
                ['label' => __('Employees'), 'value' => $sheet->entries->count()],
                ['label' => __('Eligible'), 'value' => $sheet->entries->where('eligible', true)->count(), 'tone' => 'success'],
                ['label' => __('Total bonus'), 'value' => '₹'.number_format($sheet->entries->sum('calculated'))],
                ['label' => __('Status'), 'value' => $sheet->approved_at ? __('Approved') : __('Not approved'), 'tone' => $sheet->approved_at ? 'success' : 'warn'],
            ]" />
        @endif

        <div class="card">

        @if (! $company)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Add a company first.') }}</p>
        @else
            @if ($frequency === 'monthly')
                <div class="mx-5 mt-4 rounded-md bg-warn-soft px-4 py-3 text-sm text-warn ring-1 ring-amber-200">
                    {{ __(':company pays bonus monthly with salary (Configs › Payroll), so a yearly bonus sheet is usually not needed.', ['company' => $company->establishment_name]) }}
                </div>
            @endif

            @if (! $sheet)
                <div class="px-5 py-10 text-center">
                    <p class="text-sm text-gray-600">{{ __('No bonus sheet for :year yet.', ['year' => $fy.' - '.($fy + 1)]) }}</p>
                    <form method="POST" action="{{ route('payroll.bonus.generate') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="company" value="{{ $company->uuid }}">
                        <input type="hidden" name="fy" value="{{ $fy }}">
                        <button class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ __('Work out bonus') }}</button>
                    </form>
                    <p class="mx-auto mt-4 max-w-2xl text-xs text-gray-500">{{ __('Works out each employee\'s bonus from the year\'s payslips under the Payment of Bonus Act: eligible if basic + DA is up to ₹:limit a month and they worked at least :days days; bonus is their Bonus % (8.33% to 20%) of basic + DA capped at ₹:ceiling a month.', ['limit' => number_format(config('bonus.eligibility_salary')), 'days' => config('bonus.eligibility_days'), 'ceiling' => number_format(config('bonus.calculation_ceiling'))]) }}</p>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div class="text-sm">
                        @if ($approved)
                            <span class="inline-flex rounded-full bg-success-soft px-2.5 py-0.5 text-xs font-medium text-success">{{ __('Approved') }}</span>
                            <span class="ms-2 text-gray-600">{{ __('by :name on :date', ['name' => $sheet->approver?->name ?? '-', 'date' => $sheet->approved_at?->format('d M Y')]) }}{{ $sheet->paid_on ? ' · '.__('paid :date', ['date' => $sheet->paid_on->format('d M Y')]) : '' }}</span>
                        @else
                            <span class="inline-flex rounded-full bg-warn-soft px-2.5 py-0.5 text-xs font-medium text-warn">{{ __('Draft') }}</span>
                            <span class="ms-2 text-gray-600">{{ __('Check the amounts, then approve with the date paid.') }}</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('payroll.bonus.export', [$sheet, 'xlsx']) }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Excel') }}</a>
                        <a href="{{ route('payroll.bonus.export', [$sheet, 'csv']) }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('CSV') }}</a>
                        @if ($approved)
                            <form method="POST" action="{{ route('payroll.bonus.unapprove', $sheet) }}" data-confirm="{{ __('Unapprove this bonus sheet?') }}">
                                @csrf
                                <button class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Unapprove') }}</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('payroll.bonus.generate') }}" data-confirm="{{ __('Work the bonus out again from the payslips? Amounts you changed will be reset.') }}">
                                @csrf
                                <input type="hidden" name="company" value="{{ $company->uuid }}">
                                <input type="hidden" name="fy" value="{{ $fy }}">
                                <button class="inline-flex h-control items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-4 text-sm font-medium text-ink hover:border-field hover:bg-sunken">{{ __('Work out again') }}</button>
                            </form>
                            @can('delete-records')
                                <form method="POST" action="{{ route('payroll.bonus.destroy', $sheet) }}" data-confirm="{{ __('Delete this bonus sheet?') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-danger-soft">{{ __('Delete') }}</button>
                                </form>
                            @endcan
                            <form method="POST" action="{{ route('payroll.bonus.approve', $sheet) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="date" name="paid_on" value="{{ today()->toDateString() }}" class="{{ $select }}" aria-label="{{ __('Date paid') }}">
                                <button class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ __('Approve') }}</button>
                            </form>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('payroll.bonus.update', $sheet) }}">
                    @csrf
                    @method('PUT')
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                                <tr>
                                    <th class="px-3 py-3">{{ __('Employee') }}</th>
                                    <th class="px-3 py-3 text-right">{{ __('Days worked') }}</th>
                                    <th class="px-3 py-3 text-right">{{ __('Basic + DA') }}</th>
                                    <th class="px-3 py-3 text-right">{{ __('Bonus salary') }}</th>
                                    <th class="px-3 py-3 text-right">{{ __('Bonus %') }}</th>
                                    <th class="px-3 py-3">{{ __('Eligible') }}</th>
                                    <th class="px-3 py-3 text-right">{{ __('Worked out') }}</th>
                                    <th class="px-3 py-3 text-right">{{ __('Bonus paid') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($sheet->entries as $entry)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-3">
                                            <p class="font-medium text-gray-900">{{ $entry->employee->full_name }}</p>
                                            <p class="text-xs text-gray-500">{{ $entry->employee->employee_code }}{{ $entry->employee->position ? ' · '.$entry->employee->position->name : '' }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-right">{{ $entry->days_worked + 0 }}</td>
                                        <td class="px-3 py-3 text-right">{{ $money($entry->salary) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $money($entry->bonus_salary) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $entry->rate + 0 }}%</td>
                                        <td class="px-3 py-3">
                                            @if ($entry->eligible)
                                                <span class="inline-flex rounded-full bg-success-soft px-2 py-0.5 text-xs font-medium text-success">{{ __('Yes') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ __('No') }}</span>
                                                <p class="mt-1 text-xs text-gray-500">{{ $entry->reason }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right">{{ $money($entry->calculated) }}</td>
                                        <td class="px-3 py-3 text-right">
                                            @if ($approved)
                                                <span class="font-semibold">{{ $money($entry->amount) }}</span>
                                            @else
                                                <input type="number" name="amounts[{{ $entry->id }}]" value="{{ old('amounts.'.$entry->id, $entry->amount) }}" step="0.01" min="0"
                                                       class="{{ $select }} w-32 py-1 text-right" aria-label="{{ __('Bonus for :name', ['name' => $entry->employee->full_name]) }}">
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" icon="inbox">{{ __('No salary was generated in this accounting year.') }}</x-empty-state>
                                @endforelse
                            </tbody>
                            @if ($sheet->entries->isNotEmpty())
                                <tfoot class="bg-gray-50 font-semibold text-gray-900">
                                    <tr>
                                        <td class="px-3 py-3" colspan="6">{{ __('Total') }} · {{ trans_choice(':count eligible|:count eligible', $sheet->entries->where('eligible', true)->count()) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $money($sheet->entries->sum('calculated')) }}</td>
                                        <td class="px-3 py-3 text-right">{{ $money($sheet->entries->sum('amount')) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                    @if (! $approved && $sheet->entries->isNotEmpty())
                        <div class="flex justify-end border-t border-gray-100 px-5 py-3">
                            <button class="rounded-md border border-line-strong bg-surface text-ink shadow-hairline hover:bg-sunken px-4 py-2 text-sm font-medium">{{ __('Save amounts') }}</button>
                        </div>
                    @endif
                </form>
                <p class="border-t border-gray-100 px-5 py-3 text-xs text-gray-500">
                    {{ __('From the payslips for April :from – March :to. Eligible: basic + DA up to ₹:limit a month on average and at least :days days worked. Bonus salary is basic + DA capped at ₹:ceiling a month (change it in config/bonus.php when the minimum wage is higher). The approved bonus feeds Bonus Form C and D and counts as income in TDS.', ['from' => $fy, 'to' => $fy + 1, 'limit' => number_format(config('bonus.eligibility_salary')), 'days' => config('bonus.eligibility_days'), 'ceiling' => number_format(config('bonus.calculation_ceiling'))]) }}
                </p>
            @endif
        @endif
    </div>
    </div>
</x-app-layout>
