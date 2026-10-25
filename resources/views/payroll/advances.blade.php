<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php $money = fn ($n) => number_format((float) $n, 2); @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Salary advanced list') }}</h1>
            <x-add-button modal="create-advance">{{ __('Add salary advance') }}</x-add-button>
        </div>
        <x-stat-strip :items="[
            ['label' => __('Active advances'), 'value' => $stats['count']],
            ['label' => __('Total advanced'), 'value' => '₹'.number_format($stats['given'])],
            ['label' => __('Released so far'), 'value' => '₹'.number_format($stats['released']), 'tone' => 'success'],
            ['label' => __('Still to release'), 'value' => '₹'.number_format(max($stats['given'] - $stats['released'], 0)), 'tone' => $stats['given'] > $stats['released'] ? 'warn' : null],
        ]" />

        <div class="card">

        <x-list-toolbar :action="route('payroll.advances')" :per-page="$perPage" :exports="[
            __('CSV') => route('payroll.advances.export', ['format' => 'csv'] + request()->only('search')),
            __('Excel') => route('payroll.advances.export', ['format' => 'xlsx'] + request()->only('search')),
        ]" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="px-4 py-3">{{ __('Sl') }}</th>
                        <x-sort-header column="employee" class="px-4 py-3">{{ __('Employee name') }}</x-sort-header>
                        <x-sort-header column="amount" class="px-4 py-3">{{ __('Amount') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Release amount') }}</th>
                        <x-sort-header column="month" class="px-4 py-3">{{ __('Salary month') }}</x-sort-header>
                        <x-sort-header column="date" class="px-4 py-3">{{ __('Advance date') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Attachment') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($advances as $advance)
                        @php $modal = 'edit-advance-'.$advance->uuid; @endphp
                        <tr>
                            <td class="px-4 py-3 text-gray-500">{{ $advances->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $advance->employee->full_name }} <span class="font-normal text-gray-500">({{ $advance->employee->employee_code }})</span></td>
                            <td class="px-4 py-3">{{ $money($advance->amount) }}</td>
                            <td class="px-4 py-3">{{ $money($advance->released_amount) }}</td>
                            <td class="px-4 py-3">{{ $advance->salary_month->format('Y-m') }}</td>
                            <td class="px-4 py-3">{{ $advance->advance_date->toDateString() }}</td>
                            <td class="px-4 py-3">
                                @if ($advance->attachment_path)
                                    <a href="{{ route('payroll.advances.attachment', $advance) }}" target="_blank" class="text-brand-600 hover:underline">{{ __('View') }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3"><x-status-badge :active="$advance->is_active" /></td>
                            <td class="px-4 py-3">
                                <x-row-actions :edit-modal="$modal" :delete-url="route('payroll.advances.destroy', $advance)" :delete-confirm="__('Delete this salary advance?')" />
                                <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="2xl">
                                    @include('payroll._advance-form')
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="9" icon="inbox">{{ __('No salary advances.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$advances" />
    </div>
    </div>

    <x-modal name="create-advance" :show="old('_modal') === 'create-advance'" max-width="2xl">
        @include('payroll._advance-form', ['advance' => new \App\Models\SalaryAdvance(['is_active' => true]), 'modal' => 'create-advance'])
    </x-modal>
</x-app-layout>
