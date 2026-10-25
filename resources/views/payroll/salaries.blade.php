<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    <div x-data="{ filtersOpen: @js((bool) $month) }">
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Employee salary') }}</h1>
            <button type="button" x-on:click="filtersOpen = ! filtersOpen" class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ __('Filter') }}</button>
        </div>
        <div class="card">

        <form method="GET" action="{{ route('payroll.salaries') }}" x-show="filtersOpen" x-transition class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4" style="display: none;">
            <input type="month" name="salary_month" value="{{ $month }}" class="control-inline" aria-label="{{ __('Salary month') }}">
            <button class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ __('Find') }}</button>
            <a href="{{ route('payroll.salaries') }}" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Reset') }}</a>
        </form>

        @php $exportQuery = request()->only('salary_month', 'search'); @endphp
        <x-list-toolbar :action="route('payroll.salaries')" :per-page="$perPage" copy print :exports="[
            __('CSV') => route('payroll.salaries.export', ['format' => 'csv'] + $exportQuery),
            __('Excel') => route('payroll.salaries.export', ['format' => 'xlsx'] + $exportQuery),
            __('PDF') => route('payroll.salaries.export', ['format' => 'pdf'] + $exportQuery),
        ]" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        @foreach (['Sl', 'Company name', 'Employee name', 'Salary month', 'Total salary', 'Action'] as $heading)
                            @if ($sortKey = ['Employee name' => 'employee', 'Salary month' => 'month', 'Total salary' => 'total'][$heading] ?? null)
                                <x-sort-header :column="$sortKey" class="px-4 py-3">{{ __($heading) }}</x-sort-header>
                            @else
                                <th class="px-4 py-3 {{ $heading === 'Action' ? 'print:hidden' : '' }}">{{ __($heading) }}</th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($payslips as $payslip)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $payslips->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">{{ $payslip->company->label }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $payslip->employee->full_name }}</td>
                            <td class="px-4 py-3">{{ $payslip->month->format('Y-m') }}</td>
                            <td class="px-4 py-3 font-semibold">{{ number_format((float) $payslip->net_pay, 2) }}</td>
                            <td class="px-4 py-3 print:hidden">
                                <div class="flex flex-wrap gap-1">
                                    <a href="{{ route('payroll.payslips.show', $payslip) }}" class="inline-flex h-7 items-center justify-center gap-1 rounded-control bg-primary px-2.5 text-xs font-medium text-on-primary hover:bg-primary-hover">{{ __('Payslip') }}</a>
                                    <a href="{{ route('payroll.payslips.show', ['payslip' => $payslip, 'print' => 1]) }}" target="_blank" class="inline-flex h-7 items-center justify-center gap-1 rounded-control bg-primary px-2.5 text-xs font-medium text-on-primary hover:bg-primary-hover">{{ __('Download pay slip') }}</a>
                                    @unless ($payslip->sheet->isApproved())
                                        <form method="POST" action="{{ route('payroll.payslips.regenerate', $payslip) }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="inline-flex h-7 items-center justify-center gap-1 rounded-control border border-line-strong bg-surface px-2.5 text-xs font-medium text-ink hover:bg-sunken">{{ __('Regenerate') }}</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="6" icon="inbox">{{ __('No payslips yet. Generate salary on the Salary Generate tab.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$payslips" />
    </div>
    </div>
</x-app-layout>
