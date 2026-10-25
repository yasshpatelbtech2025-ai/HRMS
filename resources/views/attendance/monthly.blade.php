<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    <x-import-errors />
    <x-bulk-upload name="import-attendance" :title="__('Import Attendance (Excel)')" :action="route('attendance.import')" :template="route('attendance.import.template')" :companies="$companies"
                   :hint="__('One row per punch: the employee code, the date and time, and IN or OUT. Punches already recorded are skipped.')" />

    @php
        $select = 'control-inline';
        $styles = [
            'P' => 'bg-success-soft text-success border-success/30',
            'HD' => 'bg-violet-100 text-violet-800 border-violet-200',
            'A' => 'bg-danger-soft text-danger border-danger/30',
            'WO' => 'bg-sunken text-ink-2 border-line-strong',
            'PH' => 'bg-info-soft text-info border-info/30',
            'LEAVE' => 'bg-teal-100 text-teal-800 border-teal-200',
            'COFF' => 'bg-primary-soft text-primary-ink border-primary/30',
        ];
        $legend = [
            'P' => [__('Present'), 'P'], 'HD' => [__('Half day'), 'HD'], 'A' => [__('Absent, loss of pay'), 'LOP'], 'MP' => [__('Missing punch'), 'MP'],
            'WO' => [__('Week off'), 'WO'], 'PH' => [__('Holiday'), 'PH'], 'LEAVE' => [__('On leave'), 'LEAVE'], 'COFF' => [__('Comp-off'), 'COFF'],
        ];
        $mpStyle = 'bg-warn-soft text-warn border-warn/40';
        $labels = ['A' => 'LOP'];
        $days = collect(range(1, $month->daysInMonth))->map(fn ($d) => $month->setDay($d));
        $employeeOptions = $allEmployees->when(request('sub_department'), fn ($c) => $c->where('sub_department_id', (int) request('sub_department')));
    @endphp

    <div
         x-data="{
             cell: {},
             leave: { start: '', end: '', days: 1 },
             choose(event, cell) {
                 const value = event.target.value;
                 event.target.value = cell.status;
                 this.cell = cell;
                 if (value === 'P') {
                     this.$dispatch('open-modal', 'attendance-time');
                 } else if (value === 'LEAVE') {
                     this.leave = { start: cell.date, end: cell.date, days: 1 };
                     this.$dispatch('open-modal', 'attendance-leave');
                 } else if (value === 'COFF') {
                     this.$nextTick(() => this.$refs.coff.submit());
                 } else {
                     this.$nextTick(() => this.$refs.reset.submit());
                 }
             },
             keys: { p: 'P', l: 'A', a: 'A', v: 'LEAVE', c: 'COFF' },
             step(event, cell) {
                 if (event.ctrlKey || event.metaKey || event.altKey) return;
                 const select = event.target;
                 const row = select.closest('tr');
                 const day = Number(select.dataset.day);
                 const arrows = { ArrowLeft: [0, -1], ArrowRight: [0, 1], ArrowUp: [-1, 0], ArrowDown: [1, 0] };
                 if (arrows[event.key]) {
                     event.preventDefault();
                     const [down, across] = arrows[event.key];
                     let target = null;
                     if (across) {
                         for (let d = day + across; d >= 1 && d <= {{ $month->daysInMonth }} && ! target; d += across) {
                             target = row.querySelector(`select[data-day='${d}']`);
                         }
                     } else {
                         for (let r = down < 0 ? row.previousElementSibling : row.nextElementSibling; r && ! target; r = down < 0 ? r.previousElementSibling : r.nextElementSibling) {
                             target = r.querySelector(`select[data-day='${day}']`);
                         }
                     }
                     target?.focus();
                     return;
                 }
                 const value = this.keys[event.key.toLowerCase()];
                 if (! value) return;
                 event.preventDefault();
                 if (! [...select.options].some((option) => option.value === value)) return;
                 select.value = value;
                 this.choose({ target: select }, cell);
             },
             countDays() {
                 if (! this.leave.start || ! this.leave.end) return;
                 const days = (new Date(this.leave.end) - new Date(this.leave.start)) / 86400000 + 1;
                 this.leave.days = days > 0 ? days : '';
             },
         }">
        <x-ui.page-header :title="__('Monthly present report')" :description="__('One row per employee, one box per day. Change a box to mark the day; the totals on the right update after saving.')">
            <x-slot:actions>
                <x-ui.button variant="secondary" type="button" onclick="window.print()">{{ __('Print') }}</x-ui.button>
                <x-ui.button type="button" x-on:click="$dispatch('open-modal', 'import-attendance')">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" /></svg>
                    {{ __('Import Attendance') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
        @if ($company && $employees->isNotEmpty())
            @php $sum = fn (string $key) => collect($grid)->sum(fn ($row) => $row['totals'][$key]) + 0; @endphp
            <x-stat-strip :items="[
                ['label' => __('Employees'), 'value' => $employees->count(), 'hint' => $month->format('F Y')],
                ['label' => __('Present days'), 'value' => $sum('P') + $sum('HD') / 2, 'tone' => 'success', 'hint' => $sum('HD') ? __(':n half days counted as half', ['n' => $sum('HD')]) : null],
                ['label' => __('LOP days'), 'value' => $sum('A'), 'tone' => $sum('A') ? 'danger' : null],
                ['label' => __('On leave'), 'value' => $sum('LEAVE'), 'hint' => __(':wo week offs · :ph holidays', ['wo' => $sum('WO'), 'ph' => $sum('PH')])],
            ]" />
        @endif

        <div class="card">

        <form method="GET" action="{{ route('attendance.index') }}" class="grid grid-cols-2 gap-x-3 gap-y-3 border-b border-line px-4 py-3.5 sm:grid-cols-3 lg:grid-cols-5 print:hidden">
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Company') }}</span><select name="company" onchange="this.form.querySelectorAll('[data-reset]').forEach(el => el.value = ''); this.form.submit()" class="{{ $select }} w-full" aria-label="{{ __('Company') }}">
                @foreach ($companies as $option)
                    <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Department') }}</span><select name="sub_department" data-reset onchange="this.form.submit()" class="{{ $select }} w-full" aria-label="{{ __('Department') }}">
                <option value="">{{ __('All departments') }}</option>
                @foreach ($subDepartments as $option)
                    <option value="{{ $option->id }}" @selected((string) request('sub_department') === (string) $option->id)>{{ $option->name }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Employee') }}</span><select name="employee" data-reset onchange="this.form.submit()" class="{{ $select }} w-full" aria-label="{{ __('Employee') }}">
                <option value="">{{ __('All employees') }}</option>
                @foreach ($employeeOptions as $option)
                    <option value="{{ $option->uuid }}" @selected(request('employee') === $option->uuid)>{{ $option->employee_code }} - {{ $option->full_name }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Year') }}</span><select name="year" onchange="this.form.submit()" class="{{ $select }} w-full" aria-label="{{ __('Year') }}">
                @foreach (range((int) date('Y'), 2020) as $year)
                    <option value="{{ $year }}" @selected($month->year === $year)>{{ $year }}</option>
                @endforeach
            </select></label>
            <label class="block min-w-0"><span class="mb-1 block text-xs font-medium text-ink-2">{{ __('Month') }}</span><select name="month" onchange="this.form.submit()" class="{{ $select }} w-full" aria-label="{{ __('Month') }}">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($month->month === $m)>{{ \Carbon\Carbon::create(2000, $m)->format('F') }}</option>
                @endforeach
            </select></label>
        </form>

        <x-input-error class="px-5 pt-3" :messages="$errors->get('date')" />

        @if (! $company)
            <x-empty-state icon="building" :title="__('No company yet')" class="py-12">{{ __('Add a company first.') }}</x-empty-state>
        @elseif ($employees->isEmpty())
            <x-empty-state icon="users" :title="__('Nobody to show')" class="py-12">{{ __('No employees were employed in :month.', ['month' => $month->format('F Y')]) }}</x-empty-state>
        @else
            <ul class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-line px-4 py-2.5 text-xs text-ink-2 print:hidden" aria-label="{{ __('Legend') }}">
                @foreach ($legend as $key => [$meaning, $code])
                    <li class="flex items-center gap-1.5">
                        <span class="inline-flex h-5 min-w-9 items-center justify-center rounded border px-1 font-mono text-2xs font-semibold {{ $key === 'MP' ? $mpStyle : $styles[$key] }}">{{ $code }}</span>{{ $meaning }}
                    </li>
                @endforeach
                <li class="flex items-center gap-1.5"><span class="inline-block size-4 rounded-sm bg-primary-soft ring-2 ring-inset ring-primary"></span>{{ __('Today') }}</li>
            </ul>
            <div class="max-h-[70vh] overflow-auto">
                <table class="min-w-full border-separate border-spacing-0 text-xs">
                    <thead class="sticky top-0 z-20 bg-surface text-center font-medium text-ink-2">
                        <tr>
                            <th class="sticky left-0 z-30 min-w-52 border-b border-e border-line bg-surface px-3 py-2 text-left">{{ __('Employee') }}</th>
                            @foreach ($days as $day)
                                <th @if ($day->isToday()) aria-current="date" @endif class="border-b border-line bg-surface px-1 py-2 {{ $day->isToday() ? 'grid-today' : ($day->isSunday() ? '!bg-sunken text-ink-3' : '') }}"><span class="font-mono text-13 font-medium {{ $day->isToday() ? 'text-primary-ink' : 'text-ink' }}">{{ $day->format('d') }}</span><br><span class="font-normal">{{ $day->format('D') }}</span></th>
                            @endforeach
                            @foreach (['P' => 'P', 'A' => 'LOP', 'PH' => 'PH', 'WO' => 'WO', 'HD' => 'HD', 'LEAVE' => 'LEAVE', 'COFF' => 'COFF'] as $total)
                                <th class="border-b border-l border-line bg-sunken px-2 py-2 font-mono">{{ $total }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            @php $row = $grid[$employee->id]; @endphp
                            <tr class="group/row">
                                <td class="sticky left-0 z-10 border-b border-e border-line bg-surface px-3 py-1.5 group-hover/row:bg-sunken">
                                    <a href="{{ route('attendance.employee-report', ['employee' => $employee->uuid, 'month' => $month->format('Y-m')]) }}" target="_blank" class="group flex items-center gap-2 rounded">
                                        <x-employee-photo :employee="$employee" class="h-8 w-8 shrink-0" />
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-medium text-ink group-hover:text-primary group-hover:underline">{{ $employee->full_name }}</span>
                                            <span class="block font-mono text-ink-3">{{ $employee->employee_code }}</span>
                                        </span>
                                    </a>
                                </td>
                                @foreach ($row['days'] as $date => $d)
                                    @php $isToday = $date === now()->toDateString(); @endphp
                                    <td class="border-b border-line px-0.5 py-1 text-center {{ $isToday ? 'grid-today' : 'group-hover/row:bg-sunken/60' }}">
                                        @if (! $d['status'])
                                            <span class="text-ink-3" aria-label="{{ __('No record') }}">–</span>
                                        @else
                                            @php
                                                $status = $d['status'];
                                                $options = match ($status) {
                                                    'P', 'HD' => [$status => $status, 'P_EDIT' => __('Edit time'), 'A' => 'LOP', 'LEAVE' => 'LEAVE'],
                                                    'WO' => ['WO' => 'WO', 'P' => 'P'],
                                                    'PH' => ['PH' => 'PH', 'P' => 'P'],
                                                    'LEAVE' => ['LEAVE' => 'LEAVE', 'A' => __('Clear')],
                                                    'COFF' => ['COFF' => 'COFF', 'P' => 'P', 'A' => __('Clear')],
                                                    default => ['A' => 'LOP', 'P' => 'P', 'LEAVE' => 'LEAVE'] + ($compOff[$employee->id] > 0 ? ['COFF' => 'COFF'] : []),
                                                };
                                                $title = collect([
                                                    $d['in'] ? __('In').' '.$d['in']->format('H:i') : null,
                                                    $d['out'] ? __('Out').' '.$d['out']->format('H:i') : null,
                                                    $d['hours'] ? $d['hours'].' '.__('hrs') : null,
                                                    $d['missing'] ? __('Missing punch') : null,
                                                    $d['late'] ? __('Late: half day penalty') : null,
                                                    $d['holiday'], $d['leave'],
                                                    $d['locked'] ? __('Locked') : null,
                                                ])->filter()->implode(' · ');
                                                // Prefill the time modal with the day's punches, or the shift's first part.
                                                $timing = $employee->shift?->timings ? collect($employee->shift->timings)->first() : ['start' => '09:00', 'end' => '18:00'];
                                                $in = $d['in'] ?? \Carbon\CarbonImmutable::parse($date.' '.$timing['start']);
                                                $out = $d['out'] ?? \Carbon\CarbonImmutable::parse($date.' '.$timing['end']);
                                                if (! $d['out'] && $out->lte($in)) {
                                                    $out = $out->addDay();
                                                }
                                                $cell = [
                                                    'employee' => $employee->uuid, 'name' => $employee->full_name, 'date' => $date, 'status' => $status,
                                                    'shift' => $employee->shift?->name, 'in' => $in->format('Y-m-d\TH:i'), 'out' => $out->format('Y-m-d\TH:i'),
                                                ];
                                            @endphp
                                            <select data-day="{{ (int) substr($date, 8, 2) }}" x-on:change="choose($event, @js($cell))" x-on:keydown="step($event, @js($cell))" title="{{ $title }}" aria-label="{{ $employee->full_name }} {{ $date }}"
                                                    class="w-16 cursor-pointer rounded border px-1 py-1 text-center font-mono text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1 {{ $d['missing'] && $status === 'A' ? $mpStyle : $styles[$status] }} {{ $d['locked'] ? 'ring-1 ring-ink-3' : '' }}">
                                                @foreach ($options as $value => $label)
                                                    <option value="{{ $value === 'P_EDIT' ? 'P' : $value }}" @selected($value === $status) class="bg-surface font-sans text-ink">
                                                        {{ $value === $status && $status === 'A' && $d['missing'] ? 'MP' : $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                @endforeach
                                @foreach (['P' => 'text-success', 'A' => 'text-danger', 'PH' => 'text-info', 'WO' => 'text-ink-2', 'HD' => 'text-violet-700', 'LEAVE' => 'text-teal-700', 'COFF' => 'text-primary-ink'] as $key => $color)
                                    <td class="border-b border-l border-line bg-sunken/60 px-2 text-center font-mono text-sm font-medium {{ $color }}">{{ $row['totals'][$key] + 0 }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="flex items-start gap-2 border-t border-line px-4 py-3 text-xs text-ink-2 print:hidden"><x-icon name="info" class="size-4 shrink-0 text-ink-3" />
                <span>{{ __('P present · HD half day · LOP absent (MP: missing punch) · WO week off · PH holiday · LEAVE · COFF compensatory off. Hover a day for its times; click a name for the day-by-day report. Keyboard: arrow keys move between days, then press P, L, V or C to mark Present, LOP, Leave or Comp-off.') }}</span>
            </p>
        @endif

        {{-- Enter In-Time & Out-Time --}}
        <x-modal name="attendance-time" :show="$errors->hasAny(['in_time', 'out_time'])" max-width="lg">
            <form method="POST" action="{{ route('attendance.monthly.time') }}">
                @csrf
                <input type="hidden" name="employee" :value="cell.employee" value="{{ old('employee') }}">
                <input type="hidden" name="date" :value="cell.date" value="{{ old('date') }}">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Enter In-Time & Out-Time') }}</h2>
                    <p class="text-sm text-ink-2"><span x-text="cell.name"></span> · <span x-text="cell.date"></span> · {{ __('Shift') }}: <span x-text="cell.shift || '—'"></span></p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <x-form-row :label="__('In-Time')" for="grid-in" required :messages="$errors->get('in_time')">
                        <input type="datetime-local" id="grid-in" name="in_time" :value="cell.in" value="{{ old('in_time') }}" required class="control">
                    </x-form-row>
                    <x-form-row :label="__('Out-Time')" for="grid-out" required :messages="$errors->get('out_time')">
                        <input type="datetime-local" id="grid-out" name="out_time" :value="cell.out" value="{{ old('out_time') }}" required class="control">
                    </x-form-row>
                </div>
                <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
                    <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        {{-- Enter Leave --}}
        <x-modal name="attendance-leave" :show="$errors->hasAny(['leave_type_id', 'start_date', 'end_date', 'days'])" max-width="lg">
            <form method="POST" action="{{ route('attendance.monthly.leave') }}">
                @csrf
                <input type="hidden" name="employee" :value="cell.employee" value="{{ old('employee') }}">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('Enter Leave') }}</h2>
                    <p class="text-sm text-ink-2" x-text="cell.name"></p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <x-form-row :label="__('Leave type')" for="grid-leave-type" required :messages="$errors->get('leave_type_id')">
                        <select id="grid-leave-type" name="leave_type_id" required class="control">
                            <option value="">{{ __('Select leave type') }}</option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) old('leave_type_id') === (string) $type->id)>{{ $type->code }} — {{ $type->name }}</option>
                            @endforeach
                        </select>
                        @if ($leaveTypes->isEmpty())
                            <p class="control-help">{{ __('This company has no leave types.') }} <a href="{{ route('attendance.leave.types') }}" class="font-medium text-primary hover:underline">{{ __('Add them first.') }}</a></p>
                        @endif
                    </x-form-row>
                    <x-form-row :label="__('From date')" for="grid-leave-start" required :messages="$errors->get('start_date')">
                        <input type="date" id="grid-leave-start" name="start_date" x-model="leave.start" x-on:change="countDays()" required class="control">
                    </x-form-row>
                    <x-form-row :label="__('End date')" for="grid-leave-end" required :messages="$errors->get('end_date')">
                        <input type="date" id="grid-leave-end" name="end_date" x-model="leave.end" x-on:change="countDays()" required class="control">
                    </x-form-row>
                    <x-form-row :label="__('Total days')" for="grid-leave-days" required :messages="$errors->get('days')">
                        <input type="number" step="0.5" min="0.5" id="grid-leave-days" name="days" x-model="leave.days" required class="control">
                    </x-form-row>
                    <x-form-row :label="__('Reason')" for="grid-leave-reason">
                        <input id="grid-leave-reason" name="reason" value="{{ old('reason') }}" class="control">
                    </x-form-row>
                </div>
                <div class="flex justify-end gap-2 border-t border-line bg-sunken/60 px-6 py-4">
                    <x-secondary-button x-on:click="$dispatch('close')">{{ __('Close') }}</x-secondary-button>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        <form x-ref="coff" method="POST" action="{{ route('attendance.monthly.coff') }}" class="hidden">
            @csrf
            <input type="hidden" name="employee" :value="cell.employee">
            <input type="hidden" name="date" :value="cell.date">
        </form>
        <form x-ref="reset" method="POST" action="{{ route('attendance.monthly.reset') }}" class="hidden">
            @csrf
            <input type="hidden" name="employee" :value="cell.employee">
            <input type="hidden" name="date" :value="cell.date">
        </form>
    </div>
    </div>
</x-app-layout>
