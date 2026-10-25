<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php($select = 'control-inline')

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Missing attendance') }}</h1>
            <a href="{{ route('attendance.raw-data') }}" class="inline-flex h-control items-center justify-center gap-2 rounded-control bg-primary px-4 text-sm font-medium text-on-primary hover:bg-primary-hover print:hidden">
                {{ __('Attendance form') }}
            </a>
        </div>
        <x-stat-strip :items="[
            ['label' => __('Missing punches'), 'value' => $punches->count(), 'tone' => $punches->isNotEmpty() ? 'warn' : 'success', 'hint' => $month->format('F Y')],
            ['label' => __('Employees affected'), 'value' => $punches->pluck('employee_id')->unique()->count()],
            ['label' => __('Days affected'), 'value' => $punches->pluck('work_date')->map(fn ($d) => $d->toDateString())->unique()->count()],
            ['label' => __('Status'), 'value' => $punches->isEmpty() ? __('All clear') : __('Needs fixing'), 'tone' => $punches->isEmpty() ? 'success' : 'warn', 'hint' => $punches->isEmpty() ? null : __('Fix these before generating salary')],
        ]" />

        <div class="card">

        <form method="GET" action="{{ route('attendance.missing-punch') }}" class="flex flex-wrap items-end gap-3 border-b border-gray-200 px-5 py-4 print:hidden">
            <label class="flex min-w-0 max-w-full items-center gap-2"><span class="shrink-0 text-13 font-medium text-ink-2">{{ __('Company') }}</span><select name="company" onchange="this.form.submit()" class="{{ $select }}">
                @foreach ($companies as $option)
                    <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                @endforeach
            </select></label>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Month') }}">
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="text-left">
                    <tr>
                        <th class="px-5 py-3">{{ __('Employee id') }}</th>
                        <th class="px-5 py-3">{{ __('Name') }}</th>
                        <th class="px-5 py-3">{{ __('Designation') }}</th>
                        <th class="px-5 py-3">{{ __('In time') }}</th>
                        <th class="px-5 py-3">{{ __('Out time') }}</th>
                        <th class="px-5 py-3">{{ __('Date') }}</th>
                        <th class="px-5 py-3">{{ __('Original shift') }}</th>
                        <th class="px-5 py-3">{{ __('Actual shift') }}</th>
                        <th class="px-5 py-3 print:hidden">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($punches as $punch)
                        <tr>
                            <td class="px-5 py-3 font-mono">{{ $punch->employee->employee_code }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $punch->employee->full_name }}</td>
                            <td class="px-5 py-3">{{ $punch->employee->position?->name }}</td>
                            <td class="px-5 py-3">{{ $punch->isIn() ? $punch->punched_at->format('H:i') : '—' }}</td>
                            <td class="px-5 py-3">{{ $punch->isIn() ? '—' : $punch->punched_at->format('H:i') }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">{{ $punch->work_date->toDateString() }}</td>
                            <td class="px-5 py-3">{{ $punch->shift?->name ?? '--' }}</td>
                            <td class="px-5 py-3 font-mono">{{ $punch->shift_code ?? '--' }}</td>
                            <td class="px-5 py-3 print:hidden">
                                <a href="{{ route('attendance.raw-data', ['employee' => $punch->employee->uuid, 'month' => $punch->work_date->format('Y-m')]) }}" class="text-sm font-medium text-primary hover:underline">{{ __('Fix') }}</a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="9" icon="inbox">{{ $company ? __('No missing punches in :month.', ['month' => $month->format('F Y')]) : __('Add a company first.') }}</x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</x-app-layout>
