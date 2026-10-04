<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />
    <x-section-tabs section="attendance.leave" nested />

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Holiday list') }}</h2>
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('attendance.leave.holiday-list') }}">
                    <label for="holiday-year" class="sr-only">{{ __('Year') }}</label>
                    <select id="holiday-year" name="year" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        @foreach (range((int) date('Y') + 1, 2020) as $option)
                            <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </form>
                <x-add-button modal="create-holiday">{{ __('Add holiday') }}</x-add-button>
            </div>
        </div>

        <x-list-toolbar :action="route('attendance.leave.holiday-list')" :per-page="$perPage" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <x-sort-header column="name" class="px-5 py-3">{{ __('Holiday name') }}</x-sort-header>
                        <x-sort-header column="from" class="px-5 py-3">{{ __('From date') }}</x-sort-header>
                        <x-sort-header column="to" class="px-5 py-3">{{ __('To date') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Total days') }}</th>
                        <th class="px-5 py-3">{{ __('Companies') }}</th>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($holidays as $holiday)
                        @php($modal = 'edit-holiday-'.$holiday->uuid)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $holidays->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $holiday->name }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $holiday->start_date->toDateString() }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $holiday->end_date->toDateString() }}</td>
                            <td class="px-5 py-3">{{ $holiday->total_days }}</td>
                            <td class="px-5 py-3">{{ $holiday->companies_count }}</td>
                            <td class="px-5 py-3">
                                <x-row-actions :edit-modal="$modal" :delete-url="route('attendance.leave.holiday-list.destroy', $holiday)"
                                               :delete-confirm="__('Delete holiday :name?', ['name' => $holiday->name])" />
                                <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="lg">
                                    @include('attendance.leave._holiday-form')
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" icon="inbox">{{ __('No holidays in :year yet.', ['year' => $year]) }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$holidays" />
    </div>

    <x-modal name="create-holiday" :show="old('_modal') === 'create-holiday'" max-width="lg">
        @include('attendance.leave._holiday-form', ['holiday' => new \App\Models\Holiday, 'modal' => 'create-holiday'])
    </x-modal>
</x-app-layout>
