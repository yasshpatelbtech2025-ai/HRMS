<x-app-layout>
    <x-slot name="header">{{ __('Payroll') }}</x-slot>

    <x-section-tabs section="payroll" />

    @php $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500'; @endphp

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Salary list') }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('payroll.export', 'csv') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('CSV') }}</a>
                <a href="{{ route('payroll.export', 'xlsx') }}" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Excel') }}</a>
            </div>
        </div>
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <form method="POST" action="{{ route('payroll.generate') }}" class="flex flex-wrap items-end gap-3 border-b border-gray-200 px-5 py-4">
            @csrf
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">{{ __('Company') }} <span class="text-red-600">*</span></span>
                <select name="company_id" required class="{{ $select }} min-w-64">
                    <option value="">{{ __('Select company') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">{{ __('Salary month') }} <span class="text-red-600">*</span></span>
                <input type="month" name="salary_month" value="{{ old('salary_month', now()->subMonth()->format('Y-m')) }}" max="{{ now()->format('Y-m') }}" required class="{{ $select }}">
            </label>
            <x-primary-button>{{ __('Generate') }}</x-primary-button>
            <div class="w-full">
                <x-input-error :messages="array_merge($errors->get('company_id'), $errors->get('salary_month'))" />
                <p class="text-xs text-gray-500">{{ __('Generating a month again replaces its payslips, until the month is approved.') }}</p>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        @foreach (['Sl', 'Company name', 'Salary name', 'Generate date', 'Generate by', 'Status', 'Approved date', 'Approved by', 'Paid date', 'Action'] as $heading)
                            <th class="whitespace-nowrap px-4 py-3">{{ __($heading) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($sheets as $sheet)
                        @php $modal = 'paid-date-'.$sheet->uuid; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $sheets->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">{{ $sheet->company->label }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $sheet->month->format('Y-m') }} <span class="text-xs font-normal text-gray-500">({{ trans_choice(':count employee|:count employees', $sheet->payslips_count) }})</span></td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $sheet->generated_at->format('d-m-Y') }}</td>
                            <td class="px-4 py-3">{{ $sheet->generator?->name }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $sheet->isApproved() ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $sheet->isApproved() ? __('Approved') : __('Not approved') }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $sheet->approved_at?->format('d-m-Y') }}</td>
                            <td class="px-4 py-3">{{ $sheet->approver?->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $sheet->paid_date?->format('d-m-Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1">
                                    <a href="{{ route('payroll.approval', $sheet) }}" class="rounded-md bg-brand-600 px-2 py-1 text-xs font-semibold text-white hover:bg-brand-700">{{ __('Salary Approval') }}</a>
                                    @if ($sheet->isApproved())
                                        <button type="button" x-data x-on:click="$dispatch('open-modal', @js($modal))" class="rounded-md bg-brand-600 px-2 py-1 text-xs font-semibold text-white hover:bg-brand-700">{{ __('Add Paid Date') }}</button>
                                    @endif
                                    <a href="{{ route('payroll.chart', $sheet) }}" class="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">{{ __('Employee Salary Chart') }}</a>
                                    @unless ($sheet->isApproved())
                                        @can('delete-records')
                                            <form method="POST" action="{{ route('payroll.destroy', $sheet) }}" data-confirm="{{ __('Delete this salary sheet and its payslips?') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    @endunless
                                </div>
                                @if ($sheet->isApproved())
                                    <x-modal :name="$modal" :show="$errors->has('paid_date') && old('_modal') === $modal" max-width="md">
                                        <form method="POST" action="{{ route('payroll.paid-date', $sheet) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="_modal" value="{{ $modal }}">
                                            <div class="border-b border-gray-200 px-6 py-4"><h2 class="text-lg font-semibold text-gray-900">{{ __('Add paid date') }}</h2></div>
                                            <div class="px-6 py-5">
                                                <x-form-row :label="__('Paid date')" for="{{ $modal }}-date" required :messages="old('_modal') === $modal ? $errors->get('paid_date') : []">
                                                    <input type="date" id="{{ $modal }}-date" name="paid_date" value="{{ $sheet->paid_date?->toDateString() ?? today()->toDateString() }}" required class="block w-full {{ $select }}">
                                                </x-form-row>
                                            </div>
                                            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                                <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
                                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                                            </div>
                                        </form>
                                    </x-modal>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="10" icon="inbox">{{ __('No salary generated yet.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sheets->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">{{ $sheets->links() }}</div>
        @endif
    </div>
    </div>
</x-app-layout>
