<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php
        $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
        $badge = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-700'];
    @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Attendance request') }}</h2>
            <div class="flex flex-wrap gap-2 print:hidden">
                @if ($company)
                    <x-add-button modal="add-attendance-request">{{ __('Add attendance request') }}</x-add-button>
                @endif
                <a href="{{ route('attendance.raw-data') }}" class="inline-flex items-center rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">{{ __('Attendance form') }}</a>
            </div>
        </div>

        <form method="GET" action="{{ route('attendance.requests') }}" class="flex flex-wrap gap-3 border-b border-gray-200 px-5 py-4 print:hidden">
            <select name="company" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Company') }}">
                @foreach ($companies as $option)
                    <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                @endforeach
            </select>
            <select name="status" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Status') }}">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </form>

        <x-input-error class="px-5 pt-3" :messages="array_merge($errors->get('date'), $errors->get('out_time'))" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-4 py-3">{{ __('Employee id') }}</th>
                        <x-sort-header column="name" class="px-4 py-3">{{ __('Name') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Designation') }}</th>
                        <th class="px-4 py-3">{{ __('In time') }}</th>
                        <th class="px-4 py-3">{{ __('Requested in time') }}</th>
                        <th class="px-4 py-3">{{ __('Out time') }}</th>
                        <th class="px-4 py-3">{{ __('Requested out time') }}</th>
                        <x-sort-header column="date" class="px-4 py-3">{{ __('Date') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Original shift') }}</th>
                        <th class="px-4 py-3">{{ __('Actual shift') }}</th>
                        <x-sort-header column="status" class="px-4 py-3">{{ __('Status') }}</x-sort-header>
                        <th class="px-4 py-3 print:hidden">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($requests ?? [] as $item)
                        @php
                            $punches = $current[$item->employee_id.'|'.$item->work_date->toDateString()] ?? collect();
                            $in = $punches->firstWhere('type', 'in');
                            $out = $punches->where('type', 'out')->last();
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono">{{ $item->employee->employee_code }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item->employee->full_name }}</td>
                            <td class="px-4 py-3">{{ $item->employee->position?->name }}</td>
                            <td class="px-4 py-3">{{ $in?->punched_at->format('H:i') ?? '--' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $item->requested_in->format('H:i') }}</td>
                            <td class="px-4 py-3">{{ $out?->punched_at->format('H:i') ?? '--' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $item->requested_out->format('H:i') }}{{ $item->requested_out->isSameDay($item->requested_in) ? '' : ' (+1)' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $item->work_date->toDateString() }}</td>
                            <td class="px-4 py-3">{{ $item->employee->shift?->name ?? '--' }}</td>
                            <td class="px-4 py-3 font-mono">{{ $in?->shift_code ?? '--' }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[$item->status] }}">{{ __(ucfirst($item->status)) }}</span>
                                @if ($item->reason)<p class="mt-1 max-w-48 text-xs text-gray-500">{{ $item->reason }}</p>@endif
                            </td>
                            <td class="px-4 py-3 print:hidden">
                                <div class="flex flex-wrap gap-1">
                                    @if ($item->status === 'pending')
                                        <form method="POST" action="{{ route('attendance.requests.approve', $item) }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="rounded-md bg-brand-600 px-2 py-1 text-xs font-semibold text-white hover:bg-brand-700">{{ __('Approve') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('attendance.requests.reject', $item) }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="rounded-md bg-amber-500 px-2 py-1 text-xs font-semibold text-white hover:bg-amber-600">{{ __('Reject') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('attendance.requests.destroy', $item) }}" data-confirm="{{ __('Delete this request?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="12" icon="inbox">{{ $company ? __('No attendance requests.') : __('Add a company first.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests)
            <x-list-footer :paginator="$requests" />
        @endif
    </div>

    @if ($company)
        <x-modal name="add-attendance-request" :show="$errors->hasAny(['employee', 'requested_in', 'requested_out'])" max-width="lg">
            <form method="POST" action="{{ route('attendance.requests.store') }}">
                @csrf
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Add attendance request') }}</h2>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <x-form-row :label="__('Employee')" for="request-employee" required :messages="$errors->get('employee')">
                        <select id="request-employee" name="employee" required class="block w-full {{ $select }}">
                            <option value="">{{ __('Select employee') }}</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->uuid }}" @selected(old('employee') === $employee->uuid)>{{ $employee->employee_code }} - {{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                    </x-form-row>
                    <x-form-row :label="__('Date')" for="request-date" required :messages="$errors->get('date')">
                        <input type="date" id="request-date" name="date" value="{{ old('date') }}" max="{{ today()->toDateString() }}" required class="block w-full {{ $select }}">
                    </x-form-row>
                    <x-form-row :label="__('Requested in time')" for="request-in" required :messages="$errors->get('requested_in')">
                        <input type="datetime-local" id="request-in" name="requested_in" value="{{ old('requested_in') }}" required class="block w-full {{ $select }}">
                    </x-form-row>
                    <x-form-row :label="__('Requested out time')" for="request-out" required :messages="$errors->get('requested_out')">
                        <input type="datetime-local" id="request-out" name="requested_out" value="{{ old('requested_out') }}" required class="block w-full {{ $select }}">
                    </x-form-row>
                    <x-form-row :label="__('Reason')" for="request-reason">
                        <input id="request-reason" name="reason" value="{{ old('reason') }}" class="block w-full {{ $select }}">
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
