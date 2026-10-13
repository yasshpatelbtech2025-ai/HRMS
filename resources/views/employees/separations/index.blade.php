<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Separation employees list') }}</h2>
            <x-add-button modal="add-separation">{{ __('Add separation') }}</x-add-button>
        </div>
        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <x-list-toolbar :action="route('employees.separations')" :per-page="$perPage" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <th class="px-5 py-3">{{ __('Company name') }}</th>
                        <x-sort-header column="code" class="px-5 py-3">{{ __('Employee id') }}</x-sort-header>
                        <x-sort-header column="name" class="px-5 py-3">{{ __('Name of employee') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Department') }}</th>
                        <th class="px-5 py-3">{{ __('Designation') }}</th>
                        <x-sort-header column="joined" class="px-5 py-3">{{ __('Joining Date') }}</x-sort-header>
                        <x-sort-header column="separated" class="px-5 py-3">{{ __('Separation Date') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employees as $employee)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $employees->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3">{{ $employee->company?->establishment_name }}</td>
                            <td class="px-5 py-3 font-mono">{{ $employee->employee_code }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $employee->full_name }}</td>
                            <td class="px-5 py-3">{{ $employee->subDepartment?->name ?? $employee->department?->name }}</td>
                            <td class="px-5 py-3">{{ $employee->position?->name }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $employee->date_of_joining?->toDateString() }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                {{ $employee->separation_date->toDateString() }}
                                @unless ($employee->isSeparated())<span class="ms-1 text-xs text-amber-700">({{ __('upcoming') }})</span>@endunless
                            </td>
                            <td class="px-5 py-3">
                                @php($modal = 'edit-separation-'.$employee->uuid)
                                <x-row-actions :edit-modal="$modal" :delete-url="route('employees.separations.destroy', $employee)"
                                               :delete-confirm="__('Remove the separation for :name? They will return to the employee list.', ['name' => $employee->full_name])">
                                    <a href="{{ route('employees.show', $employee) }}" title="{{ __('Show') }}" class="rounded-md p-2 text-sky-600 hover:bg-sky-50">
                                        <span class="sr-only">{{ __('Show') }}</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                    </a>
                                </x-row-actions>
                                <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="lg">
                                    @include('employees._date-form', [
                                        'title' => __('Edit separation'), 'action' => route('employees.separations.update', $employee), 'method' => 'PUT',
                                        'field' => 'separation_date', 'label' => __('Separation date'), 'value' => $employee->separation_date->toDateString(), 'candidates' => null,
                                    ])
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="9" icon="search">{{ request('search') ? __('No employees match your search.') : __('No separated employees.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$employees" />
    </div>
    </div>

    <x-modal name="add-separation" :show="old('_modal') === 'add-separation'" max-width="lg">
        @include('employees._date-form', [
            'modal' => 'add-separation', 'title' => __('Add separation'), 'action' => route('employees.separations.store'), 'method' => 'POST',
            'field' => 'separation_date', 'label' => __('Separation date'), 'candidates' => $candidates,
        ])
    </x-modal>
</x-app-layout>
