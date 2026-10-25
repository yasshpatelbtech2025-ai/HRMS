<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />
    <x-section-tabs section="attendance.leave" nested />

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Leave type list') }}</h1>
            <x-add-button modal="create-leave-type">{{ __('Add leave type') }}</x-add-button>
        </div>
        <div class="card">

        <x-list-toolbar :action="route('attendance.leave.types')" :per-page="$perPage" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="px-5 py-3">{{ __('Sl') }}</th>
                        <th class="px-5 py-3">{{ __('Company name') }}</th>
                        <x-sort-header column="name" class="px-5 py-3">{{ __('Leave type') }}</x-sort-header>
                        <x-sort-header column="days" class="px-5 py-3">{{ __('Days') }}</x-sort-header>
                        <th class="px-5 py-3">{{ __('Is paid') }}</th>
                        <th class="px-5 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($leaveTypes as $leaveType)
                        @php($modal = 'edit-leave-type-'.$leaveType->uuid)
                        <tr>
                            <td class="px-5 py-3 text-gray-500">{{ $leaveTypes->firstItem() + $loop->index }}</td>
                            <td class="px-5 py-3">{{ $leaveType->company?->establishment_name }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $leaveType->name }} <span class="text-gray-500">({{ $leaveType->code }})</span></td>
                            <td class="px-5 py-3">{{ rtrim(rtrim($leaveType->days, '0'), '.') }}</td>
                            <td class="px-5 py-3">{{ $leaveType->is_paid ? __('Yes') : __('No') }}</td>
                            <td class="px-5 py-3">
                                <x-row-actions :edit-modal="$modal" :delete-url="route('attendance.leave.types.destroy', $leaveType)"
                                               :delete-confirm="__('Delete leave type :name?', ['name' => $leaveType->name])" />
                                <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="2xl">
                                    @include('attendance.leave._type-form')
                                </x-modal>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="6" icon="search">{{ request('search') ? __('No leave types match your search.') : __('No leave types yet.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$leaveTypes" />
    </div>
    </div>

    <x-modal name="create-leave-type" :show="old('_modal') === 'create-leave-type'" max-width="2xl">
        @include('attendance.leave._type-form', ['leaveType' => new \App\Models\LeaveType, 'modal' => 'create-leave-type'])
    </x-modal>
</x-app-layout>
