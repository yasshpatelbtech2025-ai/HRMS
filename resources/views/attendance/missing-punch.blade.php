<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php($select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500')

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Missing attendance') }}</h2>
            <a href="{{ route('attendance.raw-data') }}" class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 print:hidden">
                {{ __('Attendance form') }}
            </a>
        </div>

        <form method="GET" action="{{ route('attendance.missing-punch') }}" class="flex flex-wrap items-end gap-3 border-b border-gray-200 px-5 py-4 print:hidden">
            <select name="company" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Company') }}">
                @foreach ($companies as $option)
                    <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                @endforeach
            </select>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Month') }}">
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-5 py-3">{{ __('Employee id') }}</th>
                        <th class="px-5 py-3">{{ __('Name') }}</th>
                        <th class="px-5 py-3">{{ __('Designation') }}</th>
                        <th class="px-5 py-3">{{ __('In time') }}</th>
                        <th class="px-5 py-3">{{ __('Out time') }}</th>
                        <th class="px-5 py-3">{{ __('Date') }}</th>
                        <th class="px-5 py-3">{{ __('Original shift') }}</th>
                        <th class="px-5 py-3">{{ __('Actual shift') }}</th>
                        <th class="px-5 py-3 print:hidden">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($punches as $punch)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono">{{ $punch->employee->employee_code }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $punch->employee->full_name }}</td>
                            <td class="px-5 py-3">{{ $punch->employee->position?->name }}</td>
                            <td class="px-5 py-3">{{ $punch->isIn() ? $punch->punched_at->format('H:i') : '—' }}</td>
                            <td class="px-5 py-3">{{ $punch->isIn() ? '—' : $punch->punched_at->format('H:i') }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $punch->work_date->toDateString() }}</td>
                            <td class="px-5 py-3">{{ $punch->shift?->name ?? '--' }}</td>
                            <td class="px-5 py-3 font-mono">{{ $punch->shift_code ?? '--' }}</td>
                            <td class="px-5 py-3 print:hidden">
                                <a href="{{ route('attendance.raw-data', ['employee' => $punch->employee->uuid, 'month' => $punch->work_date->format('Y-m')]) }}" class="text-sm font-medium text-brand-600 hover:underline">{{ __('Fix') }}</a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="9" icon="inbox">{{ $company ? __('No missing punches in :month.', ['month' => $month->format('F Y')]) : __('Add a company first.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
