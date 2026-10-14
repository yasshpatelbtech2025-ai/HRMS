<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    <div>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Employee list for id card') }}</h2>
            <x-add-button modal="assign-id-card">{{ __('Assign id card') }}</x-add-button>
        </div>
        <x-stat-strip :items="[
            ['label' => __('Employees'), 'value' => $employees->total()],
            ['label' => __('Card not yet assigned'), 'value' => $unassigned->count(), 'tone' => $unassigned->count() ? 'warn' : 'success'],
        ]" />

        <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">

        <x-list-toolbar :action="route('employees.id-cards')" :per-page="$perPage" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <x-sort-header column="code" class="px-5 py-3">{{ __('Employee id') }}</x-sort-header>
                        <x-sort-header column="name" class="px-5 py-3">{{ __('Employee name') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Position') }}</th>
                        <th class="px-5 py-3">{{ __('Contact no') }}</th>
                        <x-sort-header column="assigned" class="px-5 py-3">{{ __('Assign date') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($employees as $employee)
                        @php($modal = 'edit-id-card-'.$employee->uuid)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $employees->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3 font-mono">{{ $employee->employee_code }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $employee->full_name }}</td>
                            <td class="px-5 py-3">{{ $employee->position?->name }}</td>
                            <td class="px-5 py-3">{{ $employee->phone }}</td>
                            <td class="px-5 py-3">{{ $employee->id_card_assigned_on?->format('d M Y') ?? __('Not Assigned') }}</td>
                            <td class="px-5 py-3">
                                @php($printLink = view('employees.id-cards._print-link', ['employee' => $employee]))
                                @if ($employee->id_card_assigned_on)
                                    <x-row-actions :edit-modal="$modal" :delete-url="route('employees.id-cards.destroy', $employee)"
                                                   :delete-confirm="__('Remove the id card assignment for :name?', ['name' => $employee->full_name])">
                                        {!! $printLink !!}
                                    </x-row-actions>
                                    <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="lg">
                                        @include('employees._date-form', [
                                            'title' => __('Edit id card'), 'action' => route('employees.id-cards.update', $employee), 'method' => 'PUT',
                                            'field' => 'assigned_on', 'label' => __('Assign date'), 'value' => $employee->id_card_assigned_on->toDateString(), 'candidates' => null,
                                        ])
                                    </x-modal>
                                @else
                                    <div class="flex items-center gap-1">{!! $printLink !!}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" icon="search">{{ request('search') ? __('No employees match your search.') : __('No employees yet.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$employees" />
    </div>
    </div>

    <x-modal name="assign-id-card" :show="old('_modal') === 'assign-id-card'" max-width="lg">
        @include('employees._date-form', [
            'modal' => 'assign-id-card', 'title' => __('Assign id card'), 'action' => route('employees.id-cards.store'), 'method' => 'POST',
            'field' => 'assigned_on', 'label' => __('Assign date'), 'candidates' => $unassigned,
        ])
    </x-modal>
</x-app-layout>
