<x-app-layout>
    <x-slot name="header">{{ __('Configs') }}</x-slot>

    <x-section-tabs section="configs" />

    @php
        $select = 'control';
        $yesNo = ['0' => __('No'), '1' => __('Yes')];
        $value = fn (string $field) => old($field, is_bool($setting?->{$field}) ? (int) $setting->{$field} : $setting?->{$field});
        $questions = [
            'probation_period_months' => ['Employee Prohibition Period ? (In Months)', 'number'],
            'notice_period_days' => ['Employee Notice Period ? (In Days)', 'number'],
            'salary_structure' => ['Employee Salary Structure ?', \App\Http\Controllers\PayrollSettingController::STRUCTURES],
            'bonus_frequency' => ['Employee Bouns Paid Frequency ?', \App\Http\Controllers\PayrollSettingController::BONUS_FREQUENCIES],
            'tax_deduction' => ['Employee Tax Deducation ?', $yesNo],
            'lwf_paid_by_company' => ['LWF Contribution by Company ?', $yesNo],
            'pf_effect_on' => ['Employee PF Deduction Effect On ?', config('attendance.effect_on')],
            'esi_effect_on' => ['Employee ESI Deduction Effect On ?', ['gross' => 'On Gross Salary', 'basic' => 'On Basic Salary']],
        ];
    @endphp

    @php
        $groups = [
            [__('Employment terms'), __('Probation and notice for new and leaving employees.'), ['probation_period_months', 'notice_period_days']],
            [__('Salary and bonus'), __('How salaries are built and when bonus is paid.'), ['salary_structure', 'bonus_frequency', 'tax_deduction']],
            [__('Statutory deductions'), __('Who pays LWF, and which salary PF and ESI are worked out on.'), ['lwf_paid_by_company', 'pf_effect_on', 'esi_effect_on']],
        ];
        $help = [
            'probation_period_months' => __('How many months a new employee stays on probation.'),
            'notice_period_days' => __('Days of notice an employee gives before leaving.'),
            'salary_structure' => __('New Structure limits salary elements to Basic, Allowance and Bonus, with Basic at least half of gross.'),
            'bonus_frequency' => __('How often the bonus is paid out.'),
            'tax_deduction' => __('Yes deducts income tax (TDS) from salary every month.'),
            'lwf_paid_by_company' => __('Yes means the company pays the Labour Welfare Fund contribution.'),
            'pf_effect_on' => __('The part of the salary PF is worked out on.'),
            'esi_effect_on' => __('The part of the salary ESI is worked out on.'),
        ];
    @endphp

    <div>
        <x-ui.page-header :title="__('Payroll setting')" :description="__('Rules every salary for this company follows. Changes apply from the next salary you generate.')">
            <x-slot:actions>
                <form method="GET" action="{{ route('configs.payroll') }}" class="flex items-center gap-2">
                    <label for="payroll-company" class="text-13 font-medium text-ink-2">{{ __('Company') }}</label>
                    <select id="payroll-company" name="company" onchange="this.form.submit()" class="control-inline min-w-56">
                        @foreach ($companies as $option)
                            <option value="{{ $option->uuid }}" @selected($company?->is($option))>{{ $option->label }}</option>
                        @endforeach
                    </select>
                </form>
                @if ($company)
                    <x-add-button modal="create-payroll-rule">{{ __('Create setting') }}</x-add-button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>

        @if (! $company)
            <div class="card"><x-empty-state icon="building" :title="__('No company yet')" class="py-12">
                {{ __('Add a company first.') }}
                <x-slot name="action"><x-ui.button variant="secondary" :href="route('company.index')">{{ __('Go to Company') }}</x-ui.button></x-slot>
            </x-empty-state></div>
        @else
            <form method="POST" action="{{ route('configs.payroll.update', $company) }}" data-unsaved-warning>
                @csrf
                @method('PUT')
                <div class="card px-5 py-7 sm:px-6">
                    <p class="mb-6 text-sm text-ink-2">{{ __('Settings for') }} <span class="font-semibold text-ink">{{ $company->establishment_name }}</span></p>
                    @foreach ($groups as [$groupTitle, $groupHelp, $fields])
                        <x-ui.form-section :title="$groupTitle" :description="$groupHelp">
                            @foreach ($fields as $field)
                                @php [$question, $kind] = $questions[$field]; @endphp
                                <x-ui.field :label="__($question)" for="payroll-{{ $field }}" :name="$field" :help="$help[$field]">
                                    @if ($kind === 'number')
                                        <input type="number" min="0" id="payroll-{{ $field }}" name="{{ $field }}" value="{{ $value($field) }}" class="control px-3" aria-describedby="payroll-{{ $field }}-help">
                                    @else
                                        <select id="payroll-{{ $field }}" name="{{ $field }}" class="control" aria-describedby="payroll-{{ $field }}-help">
                                            @foreach ($kind as $option => $label)
                                                <option value="{{ $option }}" @selected((string) $value($field) === (string) $option)>{{ __($label) }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </x-ui.field>
                            @endforeach
                        </x-ui.form-section>
                    @endforeach
                </div>
                <x-ui.save-bar>
                    <x-slot:status>{{ __('Saved settings apply to salaries generated from now on.') }}</x-slot:status>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </x-ui.save-bar>
            </form>

            <div class="card mt-8">
                <div class="border-b border-line px-5 py-4"><h2 class="text-[15px] font-semibold text-ink">{{ __('Payroll rules') }}</h2><p class="mt-0.5 text-13 text-ink-2">{{ __('One-off extras such as a festival bonus, for a chosen month.') }}</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="text-left">
                            <tr>
                                <th class="px-5 py-3">{{ __('Sl') }}</th>
                                <th class="px-5 py-3">{{ __('Type') }}</th>
                                <th class="px-5 py-3">{{ __('Name') }}</th>
                                <th class="px-5 py-3">{{ __('Month') }}</th>
                                <th class="px-5 py-3">{{ __('Amount') }}</th>
                                <th class="px-5 py-3">{{ __('Status') }}</th>
                                <th class="px-5 py-3">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($rules as $rule)
                                @php $modal = 'edit-payroll-rule-'.$rule->uuid; @endphp
                                <tr>
                                    <td class="px-5 py-3 text-gray-500">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">{{ \App\Models\PayrollRule::TYPES[$rule->type] ?? $rule->type }}</td>
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $rule->name }}</td>
                                    <td class="px-5 py-3">{{ $rule->month->format('F Y') }}</td>
                                    <td class="px-5 py-3">
                                        {{ $rule->is_percent ? rtrim(rtrim($rule->amount, '0'), '.').'% '.__('of :basis', ['basis' => $rule->effect_on]) : number_format((float) $rule->amount, 2) }}
                                    </td>
                                    <td class="px-5 py-3"><x-status-badge :active="$rule->is_active" /></td>
                                    <td class="px-5 py-3">
                                        <x-row-actions :edit-modal="$modal" :delete-url="route('configs.payroll.rules.destroy', $rule)"
                                                       :delete-confirm="__('Delete rule :name?', ['name' => $rule->name])" />
                                        <x-modal :name="$modal" :show="old('_modal') === $modal" max-width="2xl">
                                            @include('configs._payroll-rule-form')
                                        </x-modal>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state colspan="7" icon="inbox">{{ __('No payroll rules yet. Use "Create setting" to add a variable bonus.') }}</x-empty-state>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if ($company)
        <x-modal name="create-payroll-rule" :show="old('_modal') === 'create-payroll-rule'" max-width="2xl">
            @include('configs._payroll-rule-form', ['rule' => new \App\Models\PayrollRule(['company_id' => $company->id, 'is_active' => true, 'effect_on' => 'basic']), 'modal' => 'create-payroll-rule'])
        </x-modal>
    @endif
</x-app-layout>
