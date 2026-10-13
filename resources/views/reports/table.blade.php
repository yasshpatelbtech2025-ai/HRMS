<x-app-layout>
    <x-slot name="header">{{ __('Reports') }}</x-slot>

    <x-section-tabs section="reports" />

    @php $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500'; @endphp

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $title }}</h2>
                <p class="text-sm text-gray-600">{{ $company?->establishment_name }} · {{ $period }}</p>
            </div>
            @include('reports._exports')
        </div>
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <form method="GET" action="{{ route('reports.'.$report) }}" class="flex flex-wrap items-end gap-3 border-b border-gray-200 px-5 py-4 print:hidden">
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">{{ __('Company') }}</span>
                <select name="company" class="{{ $select }}">
                    @foreach ($companies as $option)
                        <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                    @endforeach
                </select>
            </label>
            @if (in_array('range', $filters, true))
                <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ __('From date') }}</span>
                    <input type="date" name="from" value="{{ request('from', today()->startOfMonth()->toDateString()) }}" class="{{ $select }}"></label>
                <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ __('To date') }}</span>
                    <input type="date" name="to" value="{{ request('to', today()->toDateString()) }}" class="{{ $select }}"></label>
            @endif
            @if (in_array('month', $filters, true))
                <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ __('Month') }}</span>
                    <input type="month" name="month" value="{{ request('month', today()->format('Y-m')) }}" class="{{ $select }}"></label>
            @endif
            @if (in_array('date', $filters, true))
                <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ __('Date') }}</span>
                    <input type="date" name="date" value="{{ request('date', today()->toDateString()) }}" class="{{ $select }}"></label>
            @endif
            @if (in_array('status', $filters, true))
                <label class="text-sm text-gray-700"><span class="mb-1 block font-medium">{{ __('Employees') }}</span>
                    <select name="status" class="{{ $select }}">
                        @foreach (['current' => 'Current', 'separated' => 'Separated', 'all' => 'All'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select></label>
            @endif
            <button class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Find') }}</button>
        </form>

        @isset($note)
            <p class="px-5 pt-3 text-xs text-gray-500">{{ $note }}</p>
        @endisset

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-3 py-3">{{ __('Sl') }}</th>
                        @foreach ($headings as $heading)
                            <th class="whitespace-nowrap px-3 py-3">{{ __($heading) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-gray-500">{{ $loop->iteration }}</td>
                            @foreach ($row as $cell)
                                <td class="whitespace-nowrap px-3 py-2">{{ is_float($cell) ? rtrim(rtrim(number_format($cell, 2, '.', ''), '0'), '.') : $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <x-empty-state colspan="{{ count($headings) + 1 }}" icon="inbox">{{ $company ? __('Nothing to show for these filters.') : __('Add a company first.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="px-5 py-3 text-xs text-gray-500">{{ trans_choice(':count row|:count rows', count($rows)) }}</p>
    </div>
    </div>
</x-app-layout>
