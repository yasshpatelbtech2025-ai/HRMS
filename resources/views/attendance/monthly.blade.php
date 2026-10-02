<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php
        $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
        $styles = [
            'P' => 'bg-green-100 text-green-800 border-green-200',
            'HD' => 'bg-violet-100 text-violet-800 border-violet-200',
            'A' => 'bg-red-100 text-red-700 border-red-200',
            'WO' => 'bg-orange-100 text-orange-700 border-orange-200',
            'PH' => 'bg-blue-100 text-blue-800 border-blue-200',
            'LEAVE' => 'bg-teal-100 text-teal-800 border-teal-200',
            'COFF' => 'bg-gray-200 text-gray-700 border-gray-300',
        ];
        $labels = ['A' => 'LOP'];
        $days = collect(range(1, $month->daysInMonth))->map(fn ($d) => $month->setDay($d));
        $employeeOptions = $allEmployees->when(request('sub_department'), fn ($c) => $c->where('sub_department_id', (int) request('sub_department')));
    @endphp

    <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-200"
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
             countDays() {
                 if (! this.leave.start || ! this.leave.end) return;
                 const days = (new Date(this.leave.end) - new Date(this.leave.start)) / 86400000 + 1;
                 this.leave.days = days > 0 ? days : '';
             },
         }">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Monthly present report') }}</h2>
        </div>

        <form method="GET" action="{{ route('attendance.index') }}" class="grid grid-cols-1 gap-3 border-b border-gray-200 px-5 py-4 sm:grid-cols-3 lg:grid-cols-6 print:hidden">
            <select name="company" onchange="this.form.querySelectorAll('[data-reset]').forEach(el => el.value = ''); this.form.submit()" class="{{ $select }}" aria-label="{{ __('Company') }}">
                @foreach ($companies as $option)
                    <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                @endforeach
            </select>
            <select name="sub_department" data-reset onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Department') }}">
                <option value="">{{ __('All departments') }}</option>
                @foreach ($subDepartments as $option)
                    <option value="{{ $option->id }}" @selected((string) request('sub_department') === (string) $option->id)>{{ $option->name }}</option>
                @endforeach
            </select>
            <select name="employee" data-reset onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Employee') }}">
                <option value="">{{ __('All employees') }}</option>
                @foreach ($employeeOptions as $option)
                    <option value="{{ $option->uuid }}" @selected(request('employee') === $option->uuid)>{{ $option->employee_code }} - {{ $option->full_name }}</option>
                @endforeach
            </select>
            <select name="year" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Year') }}">
                @foreach (range((int) date('Y'), 2020) as $year)
                    <option value="{{ $year }}" @selected($month->year === $year)>{{ $year }}</option>
                @endforeach
            </select>
            <select name="month" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Month') }}">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($month->month === $m)>{{ \Carbon\Carbon::create(2000, $m)->format('F') }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.print()" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Print') }}</button>
            </div>
        </form>

        <x-input-error class="px-5 pt-3" :messages="$errors->get('date')" />

        @if (! $company)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Add a company first.') }}</p>
        @elseif ($employees->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('No employees were employed in :month.', ['month' => $month->format('F Y')]) }}</p>
        @else
            <div class="max-h-[70vh] overflow-auto">
                <table class="min-w-full border-separate border-spacing-0 text-xs">
                    <thead class="sticky top-0 z-20 bg-gray-50 text-center font-semibold text-gray-700">
                        <tr>
                            <th class="sticky left-0 z-30 min-w-52 border-b border-gray-200 bg-gray-50 px-3 py-2 text-left">{{ __('Employee') }}</th>
                            @foreach ($days as $day)
                                <th class="border-b border-gray-200 px-1 py-2 {{ $day->isSunday() ? 'text-orange-600' : '' }}">{{ $day->format('d') }}<br><span class="font-normal">{{ $day->format('D') }}</span></th>
                            @endforeach
                            @foreach (['P' => 'P', 'A' => 'LOP', 'PH' => 'PH', 'WO' => 'WO', 'HD' => 'HD', 'LEAVE' => 'LEAVE', 'COFF' => 'COFF'] as $total)
                                <th class="border-b border-l border-gray-200 bg-gray-100 px-2 py-2">{{ $total }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            @php $row = $grid[$employee->id]; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="sticky left-0 z-10 border-b border-gray-100 bg-white px-3 py-1.5">
                                    <a href="{{ route('attendance.employee-report', ['employee' => $employee->uuid, 'month' => $month->format('Y-m')]) }}" target="_blank" class="flex items-center gap-2">
                                        <x-employee-photo :employee="$employee" class="h-8 w-8 shrink-0" />
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-gray-900">{{ $employee->full_name }}</span>
                                            <span class="block text-gray-500">{{ $employee->employee_code }}</span>
                                        </span>
                                    </a>
                                </td>
                                @foreach ($row['days'] as $date => $d)
                                    <td class="border-b border-gray-100 px-0.5 py-1 text-center">
                                        @if (! $d['status'])
                                            <span class="text-gray-300">-</span>
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
                                            <select x-on:change="choose($event, @js($cell))" title="{{ $title }}" aria-label="{{ $employee->full_name }} {{ $date }}"
                                                    class="w-16 cursor-pointer rounded border px-1 py-1 text-center text-xs font-semibold {{ $d['missing'] && $status === 'A' ? 'bg-amber-100 text-amber-800 border-amber-300' : $styles[$status] }} {{ $d['locked'] ? 'ring-1 ring-gray-500' : '' }}">
                                                @foreach ($options as $value => $label)
                                                    <option value="{{ $value === 'P_EDIT' ? 'P' : $value }}" @selected($value === $status) class="bg-white text-gray-900">
                                                        {{ $value === $status && $status === 'A' && $d['missing'] ? 'MP' : $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                @endforeach
                                @foreach (['P' => 'text-green-700', 'A' => 'text-red-600', 'PH' => 'text-blue-800', 'WO' => 'text-orange-600', 'HD' => 'text-violet-700', 'LEAVE' => 'text-teal-700', 'COFF' => 'text-gray-700'] as $key => $color)
                                    <td class="border-b border-l border-gray-100 bg-gray-50 px-2 text-center text-sm font-bold {{ $color }}">{{ $row['totals'][$key] + 0 }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="px-5 py-3 text-xs text-gray-500 print:hidden">
                {{ __('P present · HD half day · LOP absent (MP: missing punch) · WO week off · PH holiday · LEAVE · COFF compensatory off. Hover a day for its times; click a name for the day-by-day report.') }}
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
                    <p class="text-sm text-gray-500"><span x-text="cell.name"></span> · <span x-text="cell.date"></span> · {{ __('Shift') }}: <span x-text="cell.shift || '—'"></span></p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <x-form-row :label="__('In-Time')" for="grid-in" required :messages="$errors->get('in_time')">
                        <input type="datetime-local" id="grid-in" name="in_time" :value="cell.in" value="{{ old('in_time') }}" required class="block w-full {{ $select }}">
                    </x-form-row>
                    <x-form-row :label="__('Out-Time')" for="grid-out" required :messages="$errors->get('out_time')">
                        <input type="datetime-local" id="grid-out" name="out_time" :value="cell.out" value="{{ old('out_time') }}" required class="block w-full {{ $select }}">
                    </x-form-row>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
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
                    <p class="text-sm text-gray-500" x-text="cell.name"></p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <x-form-row :label="__('Leave type')" for="grid-leave-type" required :messages="$errors->get('leave_type_id')">
                        <select id="grid-leave-type" name="leave_type_id" required class="block w-full {{ $select }}">
                            <option value="">{{ __('Select leave type') }}</option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) old('leave_type_id') === (string) $type->id)>{{ $type->code }} — {{ $type->name }}</option>
                            @endforeach
                        </select>
                        @if ($leaveTypes->isEmpty())
                            <p class="mt-1 text-xs text-gray-500">{{ __('This company has no leave types.') }} <a href="{{ route('attendance.leave.types') }}" class="text-indigo-600 hover:underline">{{ __('Add them first.') }}</a></p>
                        @endif
                    </x-form-row>
                    <x-form-row :label="__('From date')" for="grid-leave-start" required :messages="$errors->get('start_date')">
                        <input type="date" id="grid-leave-start" name="start_date" x-model="leave.start" x-on:change="countDays()" required class="block w-full {{ $select }}">
                    </x-form-row>
                    <x-form-row :label="__('End date')" for="grid-leave-end" required :messages="$errors->get('end_date')">
                        <input type="date" id="grid-leave-end" name="end_date" x-model="leave.end" x-on:change="countDays()" required class="block w-full {{ $select }}">
                    </x-form-row>
                    <x-form-row :label="__('Total days')" for="grid-leave-days" required :messages="$errors->get('days')">
                        <input type="number" step="0.5" min="0.5" id="grid-leave-days" name="days" x-model="leave.days" required class="block w-full {{ $select }}">
                    </x-form-row>
                    <x-form-row :label="__('Reason')" for="grid-leave-reason">
                        <input id="grid-leave-reason" name="reason" value="{{ old('reason') }}" class="block w-full {{ $select }}">
                    </x-form-row>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <x-danger-button type="button" x-on:click="$dispatch('close')">{{ __('Close') }}</x-danger-button>
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
</x-app-layout>
