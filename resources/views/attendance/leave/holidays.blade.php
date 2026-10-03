<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />
    <x-section-tabs section="attendance.leave" nested />

    @php($select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500')

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Holiday list') }}</h2>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('attendance.leave.holidays') }}" class="flex gap-2">
                    <select name="year" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Year') }}">
                        @foreach (range((int) date('Y') + 1, 2020) as $option)
                            <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <select name="company" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Company') }}">
                        @foreach ($companies as $option)
                            <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                        @endforeach
                    </select>
                </form>
                @if ($company)
                    <x-add-button modal="add-company-holiday">{{ __('Add holiday') }}</x-add-button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <th class="px-5 py-3">{{ __('Holiday name') }}</th>
                        <th class="px-5 py-3">{{ __('From date') }}</th>
                        <th class="px-5 py-3">{{ __('To date') }}</th>
                        <th class="px-5 py-3">{{ __('Total days') }}</th>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($holidays as $holiday)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $holiday->name }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $holiday->start_date->toDateString() }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $holiday->end_date->toDateString() }}</td>
                            <td class="px-5 py-3">{{ $holiday->total_days }}</td>
                            <td class="px-5 py-3">
                                <form method="POST" action="{{ route('attendance.leave.holidays.destroy', [$company, $holiday]) }}"
                                      onsubmit="return confirm(@js(__('Remove :holiday for :company?', ['holiday' => $holiday->name, 'company' => $company->establishment_name])))">
                                    @csrf
                                    @method('DELETE')
                                    <button title="{{ __('Delete') }}" class="rounded-md p-2 text-red-600 hover:bg-red-50">
                                        <span class="sr-only">{{ __('Delete') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="6" icon="inbox">
                            {{ $company ? __('No holidays for :company in :year.', ['company' => $company->establishment_name, 'year' => $year]) : __('Add a company first.') }}
                        </x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($company)
        <x-modal name="add-company-holiday" :show="$errors->has('holidays') || $errors->has('company_id')" max-width="2xl">
            <form method="POST" action="{{ route('attendance.leave.holidays.store') }}">
                @csrf
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('New holiday') }}</h2>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <x-form-row :label="__('Company')" for="holiday-company" required :messages="$errors->get('company_id')">
                        <select id="holiday-company" name="company_id" required class="block w-full {{ $select }}">
                            @foreach ($companies as $option)
                                <option value="{{ $option->id }}" @selected($company->is($option))>{{ $option->label }}</option>
                            @endforeach
                        </select>
                    </x-form-row>
                    <x-form-row :label="__('Holiday')" required :messages="array_merge($errors->get('holidays'), $errors->get('holidays.*'))">
                        <div class="max-h-64 space-y-1 overflow-y-auto rounded-md border border-gray-200 p-2">
                            @forelse ($available as $holiday)
                                <label class="flex items-center gap-2 rounded px-2 py-1 text-sm text-gray-700 hover:bg-gray-50">
                                    <input type="checkbox" name="holidays[]" value="{{ $holiday->uuid }}" @checked(in_array($holiday->uuid, old('holidays', []), true))
                                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    {{ $holiday->name }} — {{ __('Start: :start End: :end', ['start' => $holiday->start_date->toDateString(), 'end' => $holiday->end_date->toDateString()]) }}
                                </label>
                            @empty
                                <p class="px-2 py-1 text-sm text-gray-500">
                                    {{ __('Every :year holiday is already added.', ['year' => $year]) }}
                                    <a href="{{ route('attendance.leave.holiday-list', ['year' => $year]) }}" class="text-brand-600 hover:underline">{{ __('Add more to the Holiday list.') }}</a>
                                </p>
                            @endforelse
                        </div>
                    </x-form-row>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
