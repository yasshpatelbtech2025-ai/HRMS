<x-app-layout>
    <x-slot name="header">{{ __('Attendance') }}</x-slot>

    <x-section-tabs section="attendance" />

    @php
        $select = 'control-inline';
        $days = collect(range(1, $month->daysInMonth))->map(fn ($d) => $month->setDay($d));
        $codeStyles = "{ 'WO': 'bg-warn-soft text-warn', 'NS': 'bg-brand-100 text-brand-800', 'SS': 'bg-info-soft text-info', 'FS': 'bg-success-soft text-success', 'DS': 'bg-success-soft text-success', 'GS': 'bg-gray-100 text-gray-800' }";
    @endphp

    <div>
        <div class="page-head flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <h1 class="text-2xl font-semibold text-ink">{{ __('Shift management') }}</h1>
        </div>
        <div class="card">

        <form method="GET" action="{{ route('attendance.shifts') }}" class="flex flex-wrap gap-3 border-b border-gray-200 px-5 py-4">
            <select name="company" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Company') }}">
                @foreach ($companies as $option)
                    <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                @endforeach
            </select>
            <select name="year" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Year') }}">
                @foreach (range((int) date('Y') + 1, 2020) as $year)
                    <option value="{{ $year }}" @selected($month->year === $year)>{{ $year }}</option>
                @endforeach
            </select>
            <select name="month" onchange="this.form.submit()" class="{{ $select }}" aria-label="{{ __('Month') }}">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($month->month === $m)>{{ \Carbon\Carbon::create(2000, $m)->format('F') }}</option>
                @endforeach
            </select>
        </form>

        @if (! $company)
            <p class="px-5 py-10 text-center text-sm text-gray-500">{{ __('Add a company first.') }}</p>
        @elseif ($groups->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-gray-500">
                {{ __('This company has no departments yet.') }} <a href="{{ route('employees.departments') }}" class="text-brand-600 hover:underline">{{ __('Add them in Employee › Department.') }}</a>
            </p>
        @else
            <form method="POST" action="{{ route('attendance.shifts.store', $company) }}"
                  x-data="shiftRoster(@js(['groups' => $groups, 'year' => $month->year, 'month' => $month->month, 'daysInMonth' => $month->daysInMonth]))">
                @csrf
                <input type="hidden" name="year" value="{{ $month->year }}">
                <input type="hidden" name="month" value="{{ $month->month }}">
                <input type="hidden" name="roster" :value="payload">
                <x-input-error class="px-5 pt-3" :messages="collect($errors->getMessages())->flatten()->unique()->all()" />

                <div class="max-h-[70vh] overflow-auto">
                    <table class="min-w-full border-separate border-spacing-0 text-xs">
                        <thead class="sticky top-0 z-20 bg-gray-50 text-center font-semibold uppercase text-gray-700">
                            <tr>
                                <th class="border-b border-gray-200 px-2 py-2"><span class="sr-only">{{ __('Remove') }}</span></th>
                                <th class="border-b border-gray-200 px-2 py-2 text-left">{{ __('Department') }}</th>
                                <th class="border-b border-gray-200 px-2 py-2">{{ __('Shift') }}</th>
                                <th class="border-b border-gray-200 px-2 py-2">{{ __('Ittration') }}</th>
                                <th class="border-b border-gray-200 px-2 py-2 text-left">{{ __('Employee') }}</th>
                                <th class="border-b border-gray-200 px-2 py-2">{{ __('Week off') }}</th>
                                <th class="border-b border-gray-200 px-2 py-2">{{ __('Edit') }}</th>
                                @foreach ($days as $day)
                                    <th class="border-b border-gray-200 px-1 py-2 normal-case">{{ $day->day }}<br><span class="font-normal">{{ $day->format('D') }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <template x-for="group in groups" :key="group.id">
                            <tbody class="border-b border-gray-200">
                                <tr class="bg-gray-50/60">
                                    <td class="border-t border-gray-200 px-2 py-2 align-top">
                                        <button type="button" x-show="group.entries.length || group.parts.length" x-on:click="clear(group)" title="{{ __('Clear this department') }}" class="rounded p-1 text-red-600 hover:bg-danger-soft">
                                            <span class="sr-only">{{ __('Clear this department') }}</span>
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    </td>
                                    <td class="border-t border-gray-200 px-2 py-2 text-left align-top text-sm font-semibold text-gray-900" x-text="group.name"></td>
                                    <td class="border-t border-gray-200 px-2 py-2 align-top">
                                        <div class="flex flex-col gap-1 text-left">
                                            @foreach (\App\Http\Controllers\Attendance\ShiftManagementController::PARTS as $code => $label)
                                                <label class="flex items-center gap-1 whitespace-nowrap">
                                                    <input type="checkbox" value="{{ $code }}" x-model="group.parts" x-on:change="refresh(group)" class="rounded border-field text-primary focus:ring-primary/30">
                                                    {{ __($label) }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="border-t border-gray-200 px-2 py-2 align-top">
                                        <select x-model.number="group.iteration" x-on:change="refresh(group)" class="{{ $select }} text-xs" aria-label="{{ __('Ittration') }}"
                                                title="{{ __('Working days on each shift before moving to the next') }}">
                                            @foreach (\App\Http\Controllers\Attendance\ShiftManagementController::ITERATIONS as $iteration)
                                                <option value="{{ $iteration }}">{{ $iteration }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td colspan="3" class="border-t border-gray-200 px-2 py-2 align-top">
                                        <select x-on:change="add(group, $event.target.value); $event.target.value = ''" class="{{ $select }} w-full text-xs" aria-label="{{ __('Select Employee') }}">
                                            <option value="">{{ __('Select Employee') }}</option>
                                            <template x-for="e in addable(group)" :key="e.uuid"><option :value="e.uuid" x-text="e.label"></option></template>
                                        </select>
                                    </td>
                                    <td colspan="{{ $month->daysInMonth }}" class="border-t border-gray-200"></td>
                                </tr>
                                <template x-for="(entry, index) in group.entries" :key="entry.uuid">
                                    <tr class="hover:bg-gray-50">
                                        <td colspan="4" class="border-t border-gray-100"></td>
                                        <td class="whitespace-nowrap border-t border-gray-100 px-2 py-1 text-left text-sm">
                                            <span x-text="entry.label"></span>
                                            <button type="button" x-on:click="remove(group, index)" class="ms-1 font-bold text-red-600 hover:text-danger" title="{{ __('Remove from roster') }}">×</button>
                                        </td>
                                        <td class="border-t border-gray-100 px-2 py-1">
                                            <select x-model="entry.weeklyOff" x-on:change="refresh(group)" class="{{ $select }} text-xs" aria-label="{{ __('Week off') }}">
                                                @foreach (config('employee.weekdays') as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="border-t border-gray-100 px-2 py-1 text-center">
                                            <input type="checkbox" x-model="entry.manual" x-on:change="toggleManual(group, entry)" class="rounded border-field text-primary focus:ring-primary/30" aria-label="{{ __('Edit days by hand') }}">
                                        </td>
                                        @foreach ($days as $day)
                                            <td class="border-t border-gray-100 px-0.5 py-1 text-center">
                                                <template x-if="entry.manual">
                                                    <select x-model="entry.days[{{ $day->day }}]" class="w-14 rounded border-gray-300 px-1 py-0.5 text-xs" aria-label="{{ $day->toDateString() }}">
                                                        <template x-for="code in codes(group)" :key="code"><option :value="code" x-text="code"></option></template>
                                                    </select>
                                                </template>
                                                <template x-if="! entry.manual">
                                                    <span class="inline-block w-9 rounded px-1 py-0.5 font-semibold" :class="({{ $codeStyles }})[entry.days[{{ $day->day }}]]" x-text="entry.days[{{ $day->day }}]"></span>
                                                </template>
                                            </td>
                                        @endforeach
                                    </tr>
                                </template>
                            </tbody>
                        </template>
                    </table>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-gray-200 px-5 py-4">
                    <p class="text-xs text-gray-500">{{ __('FS first / day · SS second · NS night · GS general · WO week off. Rostered week-offs replace the employee\'s usual weekly leave day for this month.') }}</p>
                    <x-primary-button>{{ __('Save shifts') }}</x-primary-button>
                </div>
            </form>
        @endif
    </div>
    </div>
</x-app-layout>
