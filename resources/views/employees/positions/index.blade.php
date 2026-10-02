<x-app-layout>
    <x-slot name="header">{{ __('Employee') }}</x-slot>

    <x-section-tabs section="employees" />

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Position list') }}</h2>
            <x-add-button modal="create-position">{{ __('Add position') }}</x-add-button>
        </div>

        <x-list-toolbar :action="route('employees.positions')" :per-page="$perPage" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="w-16 px-5 py-3">{{ __('Sl') }}</th>
                        <x-sort-header column="company" default>{{ __('Company name') }}</x-sort-header>
                        <x-sort-header column="name">{{ __('Position name') }}</x-sort-header>
                        <x-sort-header column="status">{{ __('Status') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($positions as $position)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 text-gray-500">{{ $positions->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3">{{ $position->company->label }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $position->name }}</td>
                            <td class="px-5 py-3"><x-status-badge :active="$position->is_active" /></td>
                            <td class="px-5 py-3">
                                <x-row-actions :edit-modal="'edit-position-'.$position->uuid"
                                               :delete-url="route('employees.positions.destroy', $position)"
                                               :delete-confirm="__('Delete position :name?', ['name' => $position->name])" />

                                <x-modal name="edit-position-{{ $position->uuid }}" :show="old('_modal') === 'edit-position-'.$position->uuid" max-width="2xl">
                                    @include('employees.positions._form', ['modal' => 'edit-position-'.$position->uuid])
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">
                                {{ request('search') ? __('No positions match your search.') : __('No positions yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$positions" />
    </div>

    <x-modal name="create-position" :show="old('_modal') === 'create-position'" max-width="2xl">
        @include('employees.positions._form', ['position' => new \App\Models\Position, 'modal' => 'create-position'])
    </x-modal>
</x-app-layout>
