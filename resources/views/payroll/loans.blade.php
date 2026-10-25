<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php
        $money = fn ($n) => number_format((float) $n, 2);
        $exportQuery = request()->only('employee', 'search');
    @endphp

    <div x-data="{ filtersOpen: @js(request()->filled('employee')) }">
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Loan list') }}</h1>
            <div class="flex gap-2">
                <button type="button" x-on:click="filtersOpen = ! filtersOpen" class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ __('Filter') }}</button>
                <x-add-button modal="create-loan">{{ __('Add loan') }}</x-add-button>
            </div>
        </div>
        <x-stat-strip :items="[
            ['label' => __('Loans'), 'value' => $stats['count']],
            ['label' => __('Being repaid'), 'value' => $stats['running'], 'tone' => $stats['running'] ? 'warn' : null],
            ['label' => __('Fully repaid'), 'value' => $stats['repaid'], 'tone' => 'success'],
            ['label' => __('Total lent'), 'value' => '₹'.number_format($stats['lent'])],
        ]" />

        <div class="card">

        <form method="GET" action="{{ route('payroll.loans') }}" x-show="filtersOpen" x-transition class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4" style="display: none;">
            <div class="min-w-72">
                @include('payroll._employee-select', ['id' => 'loan-filter-employee', 'selected' => request('employee')])
            </div>
            <button class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ __('Find') }}</button>
            <a href="{{ route('payroll.loans') }}" class="inline-flex h-control items-center rounded-control px-3 text-sm font-medium text-ink-2 hover:bg-sunken hover:text-ink">{{ __('Reset') }}</a>
        </form>

        <x-list-toolbar :action="route('payroll.loans')" :per-page="$perPage" :exports="[
            __('CSV') => route('payroll.loans.export', ['format' => 'csv'] + $exportQuery),
            __('Excel') => route('payroll.loans.export', ['format' => 'xlsx'] + $exportQuery),
        ]" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        @foreach (['Sl', 'Company name', 'Employee name', 'Permitted by', 'Loan no', 'Amount', 'Interest rate', 'Installment period', 'Installment cleared', 'Repayment amount', 'Approved date', 'Repayment from', 'Attachment', 'Status', 'Action'] as $heading)
                            @if ($sortKey = ['Employee name' => 'employee', 'Loan no' => 'loan', 'Amount' => 'amount', 'Approved date' => 'approved', 'Status' => 'status'][$heading] ?? null)
                                <x-sort-header :column="$sortKey" class="whitespace-nowrap px-3 py-3">{{ __($heading) }}</x-sort-header>
                            @else
                                <th class="whitespace-nowrap px-3 py-3">{{ __($heading) }}</th>
                            @endif
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($loans as $loan)
                        @php $modal = 'edit-loan-'.$loan->uuid; @endphp
                        <tr>
                            <td class="px-3 py-3 text-gray-500">{{ $loans->firstItem() + $loop->index }}</td>
                            <td class="px-3 py-3">{{ $loan->employee->company?->establishment_name }}</td>
                            <td class="px-3 py-3 font-medium text-gray-900">{{ $loan->employee->full_name }}</td>
                            <td class="px-3 py-3">{{ $loan->permittedBy?->full_name ?? '—' }}</td>
                            <td class="px-3 py-3 font-mono">{{ $loan->loan_no }}</td>
                            <td class="px-3 py-3">{{ $money($loan->amount) }}</td>
                            <td class="px-3 py-3">{{ rtrim(rtrim($loan->interest_rate, '0'), '.') }}%</td>
                            <td class="px-3 py-3">{{ $loan->installment_period }}</td>
                            <td class="px-3 py-3">{{ $loan->installments_cleared }}</td>
                            <td class="px-3 py-3">{{ $money($loan->repayment_amount) }} <span class="text-xs text-gray-500">({{ $money($loan->installment) }}/{{ __('mo') }})</span></td>
                            <td class="whitespace-nowrap px-3 py-3">{{ $loan->approved_date->toDateString() }}</td>
                            <td class="px-3 py-3">{{ $loan->repayment_start->format('Y-m') }}</td>
                            <td class="px-3 py-3">
                                @if ($loan->attachment_path)
                                    <a href="{{ route('payroll.loans.attachment', $loan) }}" target="_blank" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('View') }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($loan->isRepaid())
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">{{ __('Repaid') }}</span>
                                @else
                                    <x-status-badge :active="$loan->is_active" />
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <x-row-actions :edit-modal="$modal" :delete-url="route('payroll.loans.destroy', $loan)" :delete-confirm="__('Delete loan :no?', ['no' => $loan->loan_no])" />
                                <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="2xl">
                                    @include('payroll._loan-form')
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="15" icon="inbox">{{ __('No loans.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$loans" />
    </div>
    </div>

    <x-modal name="create-loan" :show="old('_modal') === 'create-loan'" max-width="2xl">
        @include('payroll._loan-form', ['loan' => new \App\Models\Loan(['is_active' => true, 'interest_rate' => 0]), 'modal' => 'create-loan'])
    </x-modal>
</x-app-layout>
