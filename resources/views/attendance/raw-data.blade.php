<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php
        $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500';
        $exportQuery = ['employee' => $employee?->uuid, 'month' => $month->format('Y-m')];
    @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Employee attendance raw data') }}</h2>
            @if ($employee)
                <div class="flex flex-wrap gap-2 print:hidden">
                    <x-add-button modal="add-raw-data">{{ __('Add attendance data') }}</x-add-button>
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'correct-raw-data')"
                            class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-700">
                        {{ __('Attendance data correction') }}
                    </button>
                </div>
            @endif
        </div>

        <form method="GET" action="{{ route('attendance.raw-data') }}" class="flex flex-wrap items-end gap-3 border-b border-gray-200 px-5 py-4 print:hidden">
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">{{ __('Employee') }}</span>
                <select name="employee" onchange="this.form.submit()" class="{{ $select }} min-w-72">
                    <option value="">{{ __('Select employee') }}</option>
                    @foreach ($employees as $option)
                        <option value="{{ $option->uuid }}" @selected($employee?->is($option))>{{ $option->employee_code }} - {{ $option->full_name }} ({{ $option->company?->establishment_name }})</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm text-gray-700">
                <span class="mb-1 block font-medium">{{ __('Month') }}</span>
                <input type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" class="{{ $select }}">
            </label>
        </form>

        @if (! $employee)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Select an employee to see their attendance punches.') }}</p>
        @else
            <div class="px-5 pt-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ $employee->full_name }}</h3>
                <p class="text-sm text-gray-500">{{ $employee->employee_code }} · {{ $employee->company?->establishment_name }} · {{ __('Shift') }}: {{ $employee->shift?->summary ?? __('not set') }}</p>
            </div>

            <x-list-toolbar :action="route('attendance.raw-data')" :per-page="$perPage" allow-all print :exports="[
                __('CSV') => route('attendance.raw-data.export', ['format' => 'csv'] + $exportQuery),
                __('Excel') => route('attendance.raw-data.export', ['format' => 'xlsx'] + $exportQuery),
            ]" />

            <x-input-error class="px-5" :messages="$errors->get('time')" />

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                        <tr>
                            <th class="px-4 py-3">{{ __('Sl') }}</th>
                            <th class="px-4 py-3">{{ __('Device') }}</th>
                            <th class="px-4 py-3">{{ __('Time') }}</th>
                            <th class="px-4 py-3">{{ __('Action') }}</th>
                            <th class="px-4 py-3">{{ __('Type') }}</th>
                            <th class="px-4 py-3">{{ __('Is night shift') }}</th>
                            <th class="px-4 py-3">{{ __('Is missing') }}</th>
                            <th class="px-4 py-3">{{ __('Original shift') }}</th>
                            <th class="px-4 py-3">{{ __('Actual shift') }}</th>
                            <th class="px-4 py-3">{{ __('Lock') }}</th>
                            <th class="px-4 py-3 print:hidden">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($punches as $punch)
                            @php $form = 'punch-'.$punch->uuid; @endphp
                            <tr @class(['hover:bg-gray-50', 'bg-amber-50' => $punch->is_missing])>
                                <td class="px-4 py-2 text-gray-500">{{ $punches->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-2">{{ $punch->device ?? '--' }}</td>
                                <td class="px-4 py-2">
                                    <input type="datetime-local" name="time" form="{{ $form }}" value="{{ $punch->punched_at->format('Y-m-d\TH:i') }}" @disabled($punch->is_locked)
                                           onchange="this.form.requestSubmit()" aria-label="{{ __('Time') }}" class="{{ $select }} disabled:bg-gray-100">
                                </td>
                                <td class="px-4 py-2 text-xs font-semibold text-gray-600">{{ strtoupper($punch->source) }}</td>
                                <td class="px-4 py-2">
                                    <select name="type" form="{{ $form }}" @disabled($punch->is_locked) onchange="this.form.requestSubmit()" aria-label="{{ __('Type') }}" class="{{ $select }} disabled:bg-gray-100">
                                        <option value="in" @selected($punch->isIn())>{{ __('In Time') }}</option>
                                        <option value="out" @selected(! $punch->isIn())>{{ __('Out Time') }}</option>
                                    </select>
                                </td>
                                <td class="px-4 py-2">{{ (int) $punch->is_night_shift }}</td>
                                <td class="px-4 py-2 {{ $punch->is_missing ? 'font-semibold text-amber-700' : '' }}">{{ (int) $punch->is_missing }}</td>
                                <td class="px-4 py-2">{{ $punch->shift?->name ?? '--' }}</td>
                                <td class="px-4 py-2 font-mono">{{ $punch->shift_code ?? '--' }}</td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('attendance.raw-data.lock', $punch) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button title="{{ $punch->is_locked ? __('Unlock Attendance') : __('Lock Attendance') }}"
                                                class="rounded-md border px-2 py-1 text-xs {{ $punch->is_locked ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}">
                                            {{ $punch->is_locked ? __('Locked') : __('Lock') }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-2 print:hidden">
                                    <form id="{{ $form }}" method="POST" action="{{ route('attendance.raw-data.update', $punch) }}">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    @unless ($punch->is_locked)
                                        @can('delete-records')
                                            <form method="POST" action="{{ route('attendance.raw-data.destroy', $punch) }}" data-confirm="{{ __('Delete this punch?') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button title="{{ __('Delete') }}" class="rounded-md p-2 text-red-600 hover:bg-red-50">
                                                    <span class="sr-only">{{ __('Delete') }}</span>
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                </button>
                                            </form>
                                        @endcan
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="11" icon="inbox">{{ __('No punches for :name in :month.', ['name' => $employee->full_name, 'month' => $month->format('F Y')]) }}</x-empty-state>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-list-footer :paginator="$punches" />
        @endif
    </div>

    @if ($employee)
        <x-modal name="add-raw-data" :show="$errors->has('type') || ($errors->has('time') && old('_modal') === 'add-raw-data')" max-width="lg">
            <form method="POST" action="{{ route('attendance.raw-data.store') }}">
                @csrf
                <input type="hidden" name="_modal" value="add-raw-data">
                <input type="hidden" name="employee" value="{{ $employee->uuid }}">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Enter Attendance Data') }}</h2>
                    <p class="text-sm text-gray-500">{{ $employee->full_name }}</p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <x-form-row :label="__('Time')" for="raw-time" required :messages="old('_modal') === 'add-raw-data' ? $errors->get('time') : []">
                        <input type="datetime-local" id="raw-time" name="time" value="{{ old('time') }}" required max="{{ now()->format('Y-m-d\TH:i') }}" class="block w-full {{ $select }}">
                    </x-form-row>
                    <x-form-row :label="__('Type')" for="raw-type" required :messages="$errors->get('type')">
                        <select id="raw-type" name="type" required class="block w-full {{ $select }}">
                            <option value="in" @selected(old('type') === 'in')>{{ __('In Time') }}</option>
                            <option value="out" @selected(old('type') === 'out')>{{ __('Out Time') }}</option>
                        </select>
                    </x-form-row>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        @include('attendance._correction-modal', ['employee' => $employee, 'from' => $month, 'to' => $month->endOfMonth()])
    @endif
</x-app-layout>
