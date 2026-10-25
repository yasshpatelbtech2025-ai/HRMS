{{--
    Salary info section of the employee form. Expects the employeeForm
    Alpine scope plus $e, $grossLabels, $deductionLabels, $cell.
--}}
<x-form-section :title="__('Salary info')" :fields="['pay_frequency', 'hourly_rate', 'daily_rate', 'daily_hour', 'tax_regime', 'monthly_salary_calculation', 'weekly_leave_day', 'salary']">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-select-field name="pay_frequency" :label="__('Pay frequency')" :options="config('employee.pay_frequencies')" x-model="payFrequency" />
        <div x-show="payFrequency === 'hourly'">
            <x-field name="hourly_rate" type="number" step="0.01" min="0" :label="__('Hourly rate')" x-model="hourlyRate" />
        </div>
        <div x-show="payFrequency === 'daily'">
            <x-field name="daily_rate" type="number" step="0.01" min="0" :label="__('Daily rate')" x-model="dailyRate" />
        </div>
        <x-field name="daily_hour" type="number" step="0.5" min="0" max="24" :label="__('Daily hour')" :value="$e->daily_hour ?? 8" />
        <x-select-field name="tax_regime" :label="__('Tax regime')" :value="$e->tax_regime ?? 'new'" :options="config('employee.tax_regimes')" />
        <x-select-field name="weekly_leave_day" :label="__('Weekly leave day')" :value="$e->weekly_leave_day" :placeholder="__('Select day')" :options="config('employee.weekdays')" />
        <div class="sm:col-span-2 lg:col-span-3">
            <x-select-field name="monthly_salary_calculation" :label="__('Monthly salary calculation type')" :value="$e->monthly_salary_calculation"
                            :placeholder="__('Select calculation type')" :options="config('employee.monthly_salary_calculations')" />
        </div>
    </div>

    <h3 class="mt-6 text-sm font-semibold text-gray-900">{{ __('Salary structure:') }}</h3>

    @foreach ([['grossShown', $grossLabels, __('Select Gross Elements'), __('Element'), __('Monthly (value)'), __('Yearly (value)')], ['deductionsShown', $deductionLabels, __('Select Deduction Elements'), __('Element'), __('Monthly (% value)'), __('Yearly (% value)')]] as [$list, $labels, $pickerLabel, $headElement, $headMonthly, $headYearly])
        <div class="mt-3" x-data="{ picking: false }">
            <div class="relative inline-block">
                <button type="button" x-on:click="picking = ! picking" x-on:click.outside="picking = false" :aria-expanded="picking"
                        class="inline-flex h-control-sm items-center justify-center gap-1.5 rounded-control border border-line-strong bg-surface px-3 text-13 font-medium text-ink hover:border-field hover:bg-sunken">
                    {{ $pickerLabel }}
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
                </button>
                <div x-show="picking" x-transition class="absolute z-20 mt-1 w-72 rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5" style="display: none;">
                    @foreach ($labels as $key => $label)
                        @continue($key === 'basic')
                        <label class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" x-show="offered(@js($key))">
                            <input type="checkbox" :checked="{{ $list }}.includes(@js($key))" x-on:change="toggleElement(@js($list), @js($key))" class="rounded border-field text-primary focus:ring-primary/30">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-2 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left font-semibold text-gray-700">
                        <tr><th class="px-3 py-2">{{ $headElement }}</th><th class="px-3 py-2">{{ $headMonthly }}</th><th class="px-3 py-2">{{ $headYearly }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if ($list === 'deductionsShown')
                            {{-- Statutory deductions are worked out from the company size and salary. --}}
                            <template x-for="row in (statutory || [])" :key="row.label">
                                <tr class="text-gray-600">
                                    <td class="px-3 py-2" x-text="row.label"></td>
                                    <td class="px-3 py-2" x-text="money(row.monthly)"></td>
                                    <td class="px-3 py-2" x-text="money(row.yearly)"></td>
                                </tr>
                            </template>
                            <tr x-show="! statutory"><td colspan="3" class="px-3 py-2 text-xs text-gray-500">{{ __('Choose a company to see PF, ESI, Prof Tax and LWF.') }}</td></tr>
                        @endif
                        @foreach ($labels as $key => $label)
                            <tr x-show="isShown(@js($key))" @if ($key !== 'basic') style="display: none;" @endif>
                                <td class="px-3 py-2 font-medium text-gray-700">
                                    {{ $label }}
                                    @if ($key === 'basic')<span class="text-red-600" x-show="payFrequency === 'monthly'">*</span>@endif
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" min="0" name="salary[{{ $key }}][monthly]" x-model="salary.{{ $key }}.monthly" x-on:input="monthlyChanged(@js($key))"
                                           placeholder="0.00" class="{{ $cell }}" aria-label="{{ $label }} {{ __('monthly') }}">
                                    <x-input-error class="mt-1" :messages="$errors->get('salary.'.$key.'.monthly')" />
                                </td>
                                <td class="px-3 py-2">
                                    @unless ($key === 'bonus')
                                        <input type="number" step="0.01" min="0" name="salary[{{ $key }}][yearly]" x-model="salary.{{ $key }}.yearly" x-on:input="yearlyChanged(@js($key))"
                                               placeholder="0.00" class="{{ $cell }}" aria-label="{{ $label }} {{ __('yearly') }}">
                                        <x-input-error class="mt-1" :messages="$errors->get('salary.'.$key.'.yearly')" />
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                        @if ($list === 'grossShown')
                            <tr class="bg-gray-50 font-semibold text-gray-900">
                                <td class="px-3 py-2">{{ __('Gross (monthly)') }}</td>
                                <td class="px-3 py-2" x-text="money(grossMonthly)"></td>
                                <td class="px-3 py-2" x-text="money(grossMonthly * 12)"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</x-form-section>
