<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />
    <x-section-tabs section="attendance.leave" nested />

    @php
        $select = 'control-inline';
        $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, '.', ''), '0'), '.');
    @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Leave Balance Sheet') }}</h1>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('attendance.leave.balance') }}" class="flex min-w-0 flex-wrap gap-2">
                    <select name="year" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Year') }}">
                        @foreach (range((int) date('Y') + 1, 2020) as $option)
                            <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <label class="flex min-w-0 max-w-full items-center gap-2"><span class="shrink-0 text-13 font-medium text-ink-2">{{ __('Company') }}</span><select name="company" onchange="this.form.submit()" class="{{ $select }}">
                        @foreach ($companies as $option)
                            <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                        @endforeach
                    </select></label>
                </form>
                @if ($company && $types->isNotEmpty())
                    <a href="{{ route('attendance.leave.balance.export', ['company' => $company->uuid, 'year' => $year]) }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">{{ __('Export Data') }}</a>
                @endif
            </div>
        </div>
        <div class="card">

        @if (! $company)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Add a company first.') }}</p>
        @elseif ($types->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __(':company has no leave types.', ['company' => $company->establishment_name]) }} <a href="{{ route('attendance.leave.types') }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Add them first.') }}</a></p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead class="bg-gray-50 text-center font-semibold text-gray-700">
                        <tr>
                            <th rowspan="2" class="px-4 py-2 text-left">{{ __('SL') }}</th>
                            <th rowspan="2" class="px-4 py-2 text-left">{{ __('Card No') }}</th>
                            <th rowspan="2" class="px-4 py-2 text-left">{{ __('Employee Name') }}</th>
                            @foreach ($types as $type)
                                <th colspan="3" class="border-l border-gray-200 px-4 py-2">{{ $type->code }} <span class="font-normal text-gray-500">({{ $type->name }})</span></th>
                            @endforeach
                            <th rowspan="2" class="border-l border-gray-200 px-4 py-2">{{ __('Action') }}</th>
                        </tr>
                        <tr class="text-xs">
                            @foreach ($types as $type)
                                <th class="border-l border-gray-200 px-2 py-1">{{ __('Total') }}</th>
                                <th class="px-2 py-1">{{ __('Used') }}</th>
                                <th class="px-2 py-1">{{ __('Balance') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($employees as $employee)
                            @php $modal = 'balance-'.$employee->uuid; @endphp
                            <tr>
                                <td class="px-4 py-2 text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2 font-mono">{{ $employee->employee_code }}</td>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $employee->full_name }}</td>
                                @foreach ($types as $type)
                                    @php $b = $balances[$employee->id][$type->id]; @endphp
                                    <td class="border-l border-gray-100 px-2 py-2 text-center">{{ $num($b['total']) }}</td>
                                    <td class="px-2 py-2 text-center">{{ $num($b['used']) }}</td>
                                    <td class="px-2 py-2 text-center font-semibold {{ $b['balance'] < 0 ? 'text-danger' : 'text-gray-900' }}">{{ $num($b['balance']) }}</td>
                                @endforeach
                                <td class="border-l border-gray-100 px-4 py-2 text-center">
                                    <button type="button" x-data x-on:click="$dispatch('open-modal', @js($modal))" class="rounded-md p-1.5 text-brand-600 hover:bg-brand-50" title="{{ __('Edit') }}">
                                        <span class="sr-only">{{ __('Edit') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </button>
                                    <x-modal :name="$modal" max-width="lg">
                                        <div class="border-b border-gray-200 px-6 py-4 text-left">
                                            <h2 class="text-lg font-semibold text-ink">{{ __('Update leave balance') }}</h2>
                                            <p class="text-sm text-gray-500">{{ $employee->full_name }} · {{ $year }}</p>
                                        </div>
                                        <div class="divide-y divide-gray-100 px-6 text-left">
                                            @foreach ($types as $type)
                                                @php $b = $balances[$employee->id][$type->id]; @endphp
                                                <form method="POST" action="{{ route('attendance.leave.balance.update', [$employee, $type]) }}" class="flex flex-wrap items-end gap-3 py-4">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="year" value="{{ $year }}">
                                                    <h5 class="w-full text-sm font-semibold text-gray-900">{{ $type->name }} ({{ $type->code }})</h5>
                                                    <label class="text-sm text-gray-700">{{ __('Total') }}
                                                        <input type="number" step="0.5" min="0" name="total" value="{{ $num($b['total']) }}" required class="mt-1 block w-28 {{ $select }}">
                                                    </label>
                                                    <label class="text-sm text-gray-700">{{ __('Used') }}
                                                        <input type="number" step="0.5" min="0" name="used" value="{{ $num($b['used']) }}" required class="mt-1 block w-28 {{ $select }}">
                                                    </label>
                                                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                                                    <p class="w-full text-xs text-gray-500">{{ __(':days day(s) of approved leave this year.', ['days' => $num($b['approved'])]) }}</p>
                                                </form>
                                            @endforeach
                                        </div>
                                        <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
                                            <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
                                        </div>
                                    </x-modal>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="{{ 4 + $types->count() * 3 }}" icon="inbox">{{ __('No employees in :year.', ['year' => $year]) }}</x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </div>
</x-app-layout>
