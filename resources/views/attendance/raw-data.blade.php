<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php
        $select = 'control-inline';
        $exportQuery = ['employee' => $employee?->uuid, 'month' => $month->format('Y-m')];
    @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Employee attendance raw data') }}</h1>
            @if ($employee)
                <div class="flex flex-wrap gap-2 print:hidden">
                    <x-add-button modal="add-raw-data">{{ __('Add attendance data') }}</x-add-button>
                    <button type="button" x-data x-on:click="$dispatch('open-modal', 'correct-raw-data')"
                            class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover">
                        {{ __('Attendance data correction') }}
                    </button>
                </div>
            @endif
        </div>
        @if ($employee)
            <x-stat-strip :items="[
                ['label' => __('Punches this month'), 'value' => $punches->total(), 'hint' => $month->format('F Y')],
            ]" />
        @endif

        <div class="card">

        <form method="GET" action="{{ route('attendance.raw-data') }}" class="flex flex-wrap items-end gap-3 border-b border-line px-5 py-4 print:hidden">
            <label class="text-sm text-ink max-sm:w-full">
                <span class="mb-1.5 block text-13 font-medium">{{ __('Employee') }}</span>
                <select name="employee" onchange="this.form.submit()" class="{{ $select }} w-full sm:w-auto sm:min-w-72">
                    <option value="">{{ __('Select employee') }}</option>
                    @foreach ($employees as $option)
                        <option value="{{ $option->uuid }}" @selected($employee?->is($option))>{{ $option->employee_code }} - {{ $option->full_name }} ({{ $option->company?->establishment_name }})</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm text-ink max-sm:w-full">
                <span class="mb-1.5 block text-13 font-medium">{{ __('Month') }}</span>
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
                <table class="min-w-full divide-y divide-line text-sm">
                    <thead class="text-left">
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
                    <tbody class="divide-y divide-line">
                        @forelse ($punches as $punch)
                            @php $form = 'punch-'.$punch->uuid; @endphp
                            <tr @class(['hover:bg-gray-50', 'bg-warn-soft' => $punch->is_missing])>
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
                                <td class="px-4 py-2 {{ $punch->is_missing ? 'font-semibold text-warn' : '' }}">{{ (int) $punch->is_missing }}</td>
                                <td class="px-4 py-2">{{ $punch->shift?->name ?? '--' }}</td>
                                <td class="px-4 py-2 font-mono">{{ $punch->shift_code ?? '--' }}</td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('attendance.raw-data.lock', $punch) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button title="{{ $punch->is_locked ? __('Unlock Attendance') : __('Lock Attendance') }}"
                                                class="rounded-md border px-2 py-1 text-xs {{ $punch->is_locked ? 'border-ink bg-ink text-canvas' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}">
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
                                                <button title="{{ __('Delete') }}" class="rounded-md p-2 text-danger hover:bg-danger-soft">
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
    </div>

    @if ($employee)
        <x-modal name="add-raw-data" :show="$errors->has('type') || ($errors->has('time') && old('_modal') === 'add-raw-data')" max-width="lg">
            <form method="POST" action="{{ route('attendance.raw-data.store') }}">
                @csrf
                <input type="hidden" name="_modal" value="add-raw-data">
                <input type="hidden" name="employee" value="{{ $employee->uuid }}">
                <div class="border-b border-line px-6 py-4">
                    <h2 class="text-lg font-semibold text-ink">{{ __('Enter Attendance Data') }}</h2>
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
                <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
                    <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        @include('attendance._correction-modal', ['employee' => $employee, 'from' => $month, 'to' => $month->endOfMonth()])
    @endif
</x-app-layout>
