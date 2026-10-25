<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Salary adjustment list') }}</h1>
            <x-add-button modal="create-adjustment">{{ __('Add adjustment') }}</x-add-button>
        </div>
        <x-stat-strip :items="[
            ['label' => __('Adjustments'), 'value' => $stats['count']],
            ['label' => __('Added to pay'), 'value' => '₹'.number_format($stats['earnings']), 'tone' => 'success'],
            ['label' => __('Deducted from pay'), 'value' => '₹'.number_format($stats['deductions']), 'tone' => $stats['deductions'] ? 'danger' : null],
            ['label' => __('Net effect'), 'value' => '₹'.number_format($stats['earnings'] - $stats['deductions'])],
        ]" />

        <div class="card">

        <x-list-toolbar :action="route('payroll.adjustments')" :per-page="$perPage" :exports="[
            __('CSV') => route('payroll.adjustments.export', ['format' => 'csv'] + request()->only('search')),
            __('Excel') => route('payroll.adjustments.export', ['format' => 'xlsx'] + request()->only('search')),
        ]" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="px-4 py-3">{{ __('Sl') }}</th>
                        <x-sort-header column="employee" class="px-4 py-3">{{ __('Employee name') }}</x-sort-header>
                        <x-sort-header column="title" class="px-4 py-3">{{ __('Title') }}</x-sort-header>
                        <x-sort-header column="amount" class="px-4 py-3">{{ __('Amount') }}</x-sort-header>
                        <x-sort-header column="month" class="px-4 py-3">{{ __('Salary month') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Adjustment type') }}</th>
                        <th class="px-4 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($adjustments as $adjustment)
                        @php $modal = 'edit-adjustment-'.$adjustment->uuid; @endphp
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $adjustments->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $adjustment->employee->full_name }} <span class="font-normal text-gray-500">({{ $adjustment->employee->employee_code }})</span></td>
                            <td class="px-4 py-3">{{ $adjustment->title }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $adjustment->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $adjustment->salary_month->format('Y-m') }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $adjustment->type === 'earning' ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }}">{{ \App\Models\SalaryAdjustment::TYPES[$adjustment->type] }}</span></td>
                            <td class="px-4 py-3">
                                <x-row-actions :edit-modal="$modal" :delete-url="route('payroll.adjustments.destroy', $adjustment)" :delete-confirm="__('Delete this adjustment?')" />
                                <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="2xl">
                                    @include('payroll._adjustment-form')
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" icon="inbox">{{ __('No salary adjustments.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$adjustments" />
    </div>
    </div>

    <x-modal name="create-adjustment" :show="old('_modal') === 'create-adjustment'" max-width="2xl">
        @include('payroll._adjustment-form', ['adjustment' => new \App\Models\SalaryAdjustment(['type' => 'deduction']), 'modal' => 'create-adjustment'])
    </x-modal>

    <datalist id="adjustment-titles">@foreach ($titles as $title)<option value="{{ $title }}"></option>@endforeach</datalist>
</x-app-layout>
