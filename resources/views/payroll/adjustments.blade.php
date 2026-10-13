<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Salary adjustment list') }}</h2>
            <x-add-button modal="create-adjustment">{{ __('Add adjustment') }}</x-add-button>
        </div>
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <x-list-toolbar :action="route('payroll.adjustments')" :per-page="$perPage" :exports="[
            __('CSV') => route('payroll.adjustments.export', ['format' => 'csv'] + request()->only('search')),
            __('Excel') => route('payroll.adjustments.export', ['format' => 'xlsx'] + request()->only('search')),
        ]" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
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
                <tbody class="divide-y divide-gray-100">
                    @forelse ($adjustments as $adjustment)
                        @php $modal = 'edit-adjustment-'.$adjustment->uuid; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $adjustments->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $adjustment->employee->full_name }} <span class="font-normal text-gray-500">({{ $adjustment->employee->employee_code }})</span></td>
                            <td class="px-4 py-3">{{ $adjustment->title }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $adjustment->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ $adjustment->salary_month->format('Y-m') }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $adjustment->type === 'earning' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">{{ \App\Models\SalaryAdjustment::TYPES[$adjustment->type] }}</span></td>
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
