<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />
    <x-section-tabs section="attendance.leave" nested />

    @php
        $input = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500';
        $exportQuery = request()->only('status', 'search');
        $badge = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-700'];
    @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Leave list') }}</h2>
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('attendance.leave') }}">
                    <select name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500" aria-label="{{ __('Status') }}">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach (\App\Http\Controllers\Attendance\LeaveController::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </form>
                <x-add-button modal="create-leave">{{ __('Add leave application') }}</x-add-button>
            </div>
        </div>

        <x-list-toolbar :action="route('attendance.leave')" :per-page="$perPage" :exports="[
            __('CSV') => route('attendance.leave.export', ['format' => 'csv'] + $exportQuery),
            __('Excel') => route('attendance.leave.export', ['format' => 'xlsx'] + $exportQuery),
        ]" />

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                    <tr>
                        <th class="px-4 py-3">{{ __('Sl') }}</th>
                        <x-sort-header column="employee" class="px-4 py-3">{{ __('Employee name') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <x-sort-header column="applied" class="px-4 py-3">{{ __('Apply date') }}</x-sort-header>
                        <x-sort-header column="start" class="px-4 py-3">{{ __('Leave start date') }}</x-sort-header>
                        <x-sort-header column="end" class="px-4 py-3">{{ __('Leave end date') }}</x-sort-header>
                        <x-sort-header column="days" class="px-4 py-3">{{ __('Days') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Hard copy') }}</th>
                        <th class="px-4 py-3">{{ __('Is paid') }}</th>
                        <x-sort-header column="status" class="px-4 py-3">{{ __('Status') }}</x-sort-header>
                        <th class="px-4 py-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($leaves as $leave)
                        @php
                            $approved = $leave->status === 'approved';
                            $modal = 'approve-leave-'.$leave->uuid;
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $leaves->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $leave->employee->full_name }} <span class="font-normal text-gray-500">({{ $leave->employee->employee_code }})</span></td>
                            <td class="px-4 py-3">{{ $leave->leaveType->code }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $leave->created_at->toDateString() }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ ($approved ? $leave->approved_start_date : $leave->start_date)->toDateString() }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ ($approved ? $leave->approved_end_date : $leave->end_date)->toDateString() }}</td>
                            <td class="px-4 py-3">{{ rtrim(rtrim($approved ? $leave->approved_days : $leave->days, '0'), '.') }}</td>
                            <td class="px-4 py-3">
                                @if ($leave->hard_copy_path)
                                    <a href="{{ route('attendance.leave.hard-copy', $leave) }}" target="_blank" class="text-brand-600 hover:underline">{{ __('View') }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $leave->leaveType->is_paid ? __('Yes') : __('No') }}</td>
                            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[$leave->status] }}">{{ __(ucfirst($leave->status)) }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-1">
                                    @unless ($approved)
                                        <button type="button" x-data x-on:click="$dispatch('open-modal', @js($modal))" class="rounded-md bg-brand-600 px-2 py-1 text-xs font-semibold text-white hover:bg-brand-700">{{ __('Approve') }}</button>
                                    @endunless
                                    @if ($leave->status !== 'rejected')
                                        <form method="POST" action="{{ route('attendance.leave.reject', $leave) }}" data-confirm="{{ __('Reject this leave?') }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="rounded-md bg-amber-500 px-2 py-1 text-xs font-semibold text-white hover:bg-amber-600">{{ __('Reject') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('attendance.leave.destroy', $leave) }}" data-confirm="{{ __('Delete this leave application?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">{{ __('Delete') }}</button>
                                    </form>
                                </div>

                                @unless ($approved)
                                    <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="lg">
                                        <form method="POST" action="{{ route('attendance.leave.approve', $leave) }}"
                                              x-data="{ start: @js(old('approved_start_date', $leave->start_date->toDateString())), end: @js(old('approved_end_date', $leave->end_date->toDateString())), days: @js(old('approved_days', rtrim(rtrim($leave->days, '0'), '.'))) }">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="_modal" value="{{ $modal }}">
                                            <div class="border-b border-gray-200 px-6 py-4">
                                                <h2 class="text-lg font-semibold text-gray-900">{{ __('Application approved by manager') }}</h2>
                                                <p class="text-sm text-gray-500">{{ $leave->employee->full_name }} · {{ $leave->leaveType->name }}</p>
                                            </div>
                                            <div class="space-y-4 px-6 py-5">
                                                @php $mine = old('_modal') === $modal; @endphp
                                                <x-form-row :label="__('From date')" required :messages="$mine ? $errors->get('approved_start_date') : []">
                                                    <input type="date" name="approved_start_date" x-model="start" x-on:change="days = Math.max(1, (new Date(end) - new Date(start)) / 86400000 + 1)" required class="{{ $input }}">
                                                </x-form-row>
                                                <x-form-row :label="__('End date')" required :messages="$mine ? $errors->get('approved_end_date') : []">
                                                    <input type="date" name="approved_end_date" x-model="end" x-on:change="days = Math.max(1, (new Date(end) - new Date(start)) / 86400000 + 1)" required class="{{ $input }}">
                                                </x-form-row>
                                                <x-form-row :label="__('Total days')" :messages="$mine ? $errors->get('approved_days') : []">
                                                    <input type="number" step="0.5" min="0.5" name="approved_days" x-model="days" class="{{ $input }}">
                                                </x-form-row>
                                                <x-form-row :label="__('Description')">
                                                    <textarea name="description" rows="2" class="{{ $input }}">{{ $mine ? old('description') : '' }}</textarea>
                                                </x-form-row>
                                            </div>
                                            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                                                <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
                                                <x-primary-button>{{ __('Approve') }}</x-primary-button>
                                            </div>
                                        </form>
                                    </x-modal>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="11" icon="inbox">{{ __('No leave applications.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-list-footer :paginator="$leaves" />
    </div>

    <x-modal name="create-leave" :show="$errors->hasAny(['employee', 'leave_type_id', 'start_date', 'end_date', 'days', 'hard_copy'])" max-width="2xl">
        <form method="POST" action="{{ route('attendance.leave.store') }}" enctype="multipart/form-data"
              x-data="{
                  employee: @js(old('employee', '')),
                  employees: @js($employees->map(fn ($e) => ['uuid' => $e->uuid, 'company_id' => $e->company_id])),
                  types: @js($leaveTypes),
                  start: @js(old('start_date', '')), end: @js(old('end_date', '')), days: @js(old('days', '')),
                  get company() { return this.employees.find(e => e.uuid === this.employee)?.company_id },
                  get companyTypes() { return this.types.filter(t => t.company_id === this.company) },
                  count() { if (this.start && this.end) this.days = Math.max(1, (new Date(this.end) - new Date(this.start)) / 86400000 + 1) },
              }">
            @csrf
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Leave application create') }}</h2>
            </div>
            <div class="space-y-4 px-6 py-5">
                <x-form-row :label="__('Employee')" for="leave-employee" required :messages="$errors->get('employee')">
                    <select id="leave-employee" name="employee" x-model="employee" required class="{{ $input }}">
                        <option value="">{{ __('Select employee') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->uuid }}">{{ $employee->employee_code }} - {{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                </x-form-row>
                <x-form-row :label="__('Leave type')" for="leave-type" required :messages="$errors->get('leave_type_id')">
                    <select id="leave-type" name="leave_type_id" required class="{{ $input }}" :disabled="! employee">
                        <option value="">{{ __('Select leave type') }}</option>
                        <template x-for="t in companyTypes" :key="t.id"><option :value="t.id" x-text="t.code + ' — ' + t.name" :selected="String(t.id) === @js((string) old('leave_type_id'))"></option></template>
                    </select>
                    <p class="mt-1 text-xs text-gray-500" x-show="employee && ! companyTypes.length">
                        {{ __("This employee's company has no leave types.") }} <a href="{{ route('attendance.leave.types') }}" class="text-brand-600 hover:underline">{{ __('Add them first.') }}</a>
                    </p>
                </x-form-row>
                <x-form-row :label="__('From date')" for="leave-start" required :messages="$errors->get('start_date')">
                    <input type="date" id="leave-start" name="start_date" x-model="start" x-on:change="count()" required class="{{ $input }}">
                </x-form-row>
                <x-form-row :label="__('End date')" for="leave-end" required :messages="$errors->get('end_date')">
                    <input type="date" id="leave-end" name="end_date" x-model="end" x-on:change="count()" required class="{{ $input }}">
                </x-form-row>
                <x-form-row :label="__('Total days')" for="leave-days" :messages="$errors->get('days')">
                    <input type="number" step="0.5" min="0.5" id="leave-days" name="days" x-model="days" class="{{ $input }}">
                </x-form-row>
                <x-form-row :label="__('Application hard copy')" for="leave-hard-copy" :messages="$errors->get('hard_copy')">
                    <input type="file" id="leave-hard-copy" name="hard_copy" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200">
                </x-form-row>
                <x-form-row :label="__('Reason')" for="leave-reason">
                    <textarea id="leave-reason" name="reason" rows="2" class="{{ $input }}">{{ old('reason') }}</textarea>
                </x-form-row>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
