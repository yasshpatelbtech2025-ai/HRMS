@php
    use App\Models\Employee;

    $isNew = ! $employee->exists;
    $value = fn (string $name) => old($name, $employee->$name instanceof \Carbon\CarbonInterface ? $employee->$name->format('Y-m-d') : $employee->$name);

    // Sections of the form. Field: [name, label, options]. Options: type, required, rules, mask, hint, span, mono, attrs.
    $sections = [
        ['key' => 'employment', 'title' => __('Employment'), 'description' => __('Where they work and since when.')],
        ['key' => 'personal', 'title' => __('Personal details'), 'description' => __('Name, contact and family details.')],
        ['key' => 'statutory', 'title' => __('Statutory IDs'), 'description' => __('Needed for PF, ESIC and TDS filings.')],
        ['key' => 'bank', 'title' => __('Bank account'), 'description' => __('Where salary is paid.')],
    ];
    $fieldSection = [
        'company_id' => 0, 'employee_code' => 0, 'department_id' => 0, 'position_id' => 0, 'joining_date' => 0, 'gross_salary' => 0,
        'status' => 0, 'separation_date' => 0, 'separation_reason' => 0,
        'first_name' => 1, 'middle_name' => 1, 'last_name' => 1, 'father_name' => 1, 'gender' => 1, 'date_of_birth' => 1,
        'marital_status' => 1, 'mobile' => 1, 'email' => 1, 'address' => 1,
        'aadhaar_number' => 2, 'pan_number' => 2, 'uan_number' => 2, 'esic_number' => 2,
        'bank_name' => 3, 'bank_account_number' => 3, 'ifsc_code' => 3,
    ];
    $errorSections = collect($errors->keys())->map(fn ($k) => $fieldSection[$k] ?? 0)->unique()->sort()->values()->all();
    $start = $errorSections[0] ?? 0;
    $title = $isNew ? __('Add employee') : $employee->full_name;
@endphp

<x-app-layout :title="$title">
    <x-ui.page-header :title="$title">
        @unless ($isNew)
            <x-slot:description>
                <span class="font-mono">{{ $employee->employee_code }}</span> · {{ $employee->company?->establishment_name }}
                · <x-ui.badge :tone="$employee->status === Employee::ACTIVE ? 'success' : 'neutral'" dot>{{ $employee->status === Employee::ACTIVE ? __('Active') : __('Separated') }}</x-ui.badge>
            </x-slot:description>
        @endunless
    </x-ui.page-header>

    <form method="POST" action="{{ $isNew ? route('employees.store') : route('employees.update', $employee) }}" data-guard novalidate
          x-data="steppedForm({ steps: @js(array_column($sections, 'title')), start: {{ $start }}, stepped: {{ $isNew ? 'true' : 'false' }}, errors: @js($errorSections) })"
          @submit="submit($event)"
          class="pb-24">
        @csrf
        @if (! $isNew) @method('PUT') @endif

        <div x-data="{ companyId: @js((string) $value('company_id')), departments: @js($departments), status: @js($value('status') ?? Employee::ACTIVE) }"
             class="grid gap-6 lg:grid-cols-[13rem_1fr]">

            {{-- Progress / section navigation --}}
            <nav aria-label="{{ __('Form sections') }}" class="lg:sticky lg:top-[calc(var(--topbar-h)+1.5rem)] lg:self-start">
                {{-- Mobile: compact progress bar --}}
                <div class="lg:hidden" x-show="stepped">
                    <div class="mb-1.5 flex items-center justify-between text-13">
                        <span class="font-medium text-ink" x-text="steps[step]"></span>
                        <span class="text-ink-3">{{ __('Step') }} <span x-text="step + 1"></span> {{ __('of') }} {{ count($sections) }}</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-sunken" role="progressbar" aria-valuemin="1" :aria-valuenow="step + 1" aria-valuemax="{{ count($sections) }}">
                        <div class="h-full rounded-full bg-primary transition-all duration-300" :style="`width: ${(step + 1) / steps.length * 100}%`"></div>
                    </div>
                </div>

                <ol class="space-y-0.5 max-lg:hidden" :class="!stepped && 'max-lg:!hidden'">
                    @foreach ($sections as $i => $section)
                        <li>
                            <button type="button" @click="go({{ $i }})" :aria-current="stepped && step === {{ $i }} ? 'step' : null"
                                    :class="stepped && step === {{ $i }} ? 'bg-surface shadow-hairline border-line' : 'border-transparent hover:bg-sunken'"
                                    class="flex w-full items-center gap-3 rounded-control border px-2.5 py-2 text-start transition-colors">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-medium transition-colors"
                                      :class="{
                                          'border-danger bg-danger-soft text-danger': state({{ $i }}) === 'error',
                                          'border-primary bg-primary text-white': state({{ $i }}) === 'current',
                                          'border-primary/30 bg-primary-soft text-primary': state({{ $i }}) === 'done',
                                          'border-line-strong text-ink-3': state({{ $i }}) === 'todo',
                                      }">
                                    <template x-if="state({{ $i }}) === 'done'"><x-icon name="check" class="size-3.5" stroke="2.5" /></template>
                                    <template x-if="state({{ $i }}) === 'error'"><span>!</span></template>
                                    <template x-if="!['done', 'error'].includes(state({{ $i }}))"><span>{{ $i + 1 }}</span></template>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm" :class="stepped && step === {{ $i }} ? 'font-medium text-ink' : 'text-ink-2'">{{ $section['title'] }}</span>
                                    <span class="block truncate text-xs text-ink-3">{{ $section['description'] }}</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ol>

                @if ($isNew)
                    <label class="mt-4 flex cursor-pointer items-center gap-2 px-2.5 text-13 text-ink-3 max-lg:hidden">
                        <x-ui.checkbox ::checked="!stepped" @change="stepped = !$event.target.checked" />
                        {{ __('Show all sections on one page') }}
                    </label>
                @endif
            </nav>

            <div class="flex min-w-0 flex-col gap-5">
                {{-- 1. Employment --}}
                <x-ui.card data-step="0" x-show="isVisible(0)" id="section-employment" class="scroll-mt-24">
                    <x-slot:title>{{ $sections[0]['title'] }}</x-slot:title>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.field :label="__('Company')" for="company_id" required>
                            <x-ui.combobox name="company_id" x-model="companyId" :value="$value('company_id')" :placeholder="__('Search company…')"
                                           :options="$companies->map(fn ($c) => ['value' => $c->id, 'label' => $c->establishment_name])->all()"
                                           rules="required" :label="__('Company')" />
                        </x-ui.field>
                        <x-ui.field :label="__('Employee ID')" for="employee_code" :hint="__('Leave blank and the next number is used automatically.')">
                            <x-ui.input name="employee_code" :value="$value('employee_code')" mono maxlength="20" :placeholder="__('Auto')" />
                        </x-ui.field>
                        <x-ui.field :label="__('Department')" for="department_id" :hint="__('Departments of the selected company.')">
                            <x-ui.combobox name="department_id" :value="$value('department_id')" :placeholder="__('None')" :empty-label="__('None')"
                                           options-from="departments.filter(d => String(d.company_id) === String(companyId)).map(d => ({ value: d.id, label: d.name }))" />
                        </x-ui.field>
                        <x-ui.field :label="__('Designation')" for="position_id">
                            <x-ui.combobox name="position_id" :value="$value('position_id')" :placeholder="__('None')" :empty-label="__('None')"
                                           :options="$positions->map(fn ($p) => ['value' => $p->id, 'label' => $p->name])->all()" />
                        </x-ui.field>
                        <x-ui.field :label="__('Joining date')" for="joining_date" required>
                            <x-ui.input name="joining_date" type="date" :value="$value('joining_date')" data-rules="required" :data-label="__('Joining date')" />
                        </x-ui.field>
                        <x-ui.field :label="__('Gross salary (monthly)')" for="gross_salary">
                            <x-ui.input name="gross_salary" type="number" step="0.01" min="0" inputmode="decimal" prefix="₹" mono :value="$value('gross_salary')" />
                        </x-ui.field>

                        <x-ui.field :label="__('Status')" for="status" tag="fieldset" class="sm:col-span-2">
                            <div class="inline-flex rounded-control border border-line-strong bg-sunken p-0.5">
                                @foreach ([Employee::ACTIVE => __('Active'), Employee::SEPARATED => __('Separated')] as $key => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="status" value="{{ $key }}" x-model="status" class="peer sr-only" @checked($value('status') === $key)>
                                        <span class="block rounded-[5px] px-3.5 py-1.5 text-sm text-ink-2 transition peer-checked:bg-surface peer-checked:font-medium peer-checked:text-ink peer-checked:shadow-hairline peer-focus-visible:ring-2 peer-focus-visible:ring-primary">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </x-ui.field>

                        <div x-show="status === '{{ Employee::SEPARATED }}'" x-collapse class="grid gap-5 sm:col-span-2 sm:grid-cols-2">
                            <x-ui.field :label="__('Separation date')" for="separation_date" required>
                                <x-ui.input name="separation_date" type="date" :value="$value('separation_date')" data-rules="required-if:status,separated|not-before:joining_date" :data-label="__('Separation date')" />
                            </x-ui.field>
                            <x-ui.field :label="__('Reason')" for="separation_reason">
                                <x-ui.input name="separation_reason" :value="$value('separation_reason')" :placeholder="__('e.g. Resigned, contract ended')" />
                            </x-ui.field>
                        </div>
                    </div>
                </x-ui.card>

                {{-- 2. Personal --}}
                <x-ui.card data-step="1" x-show="isVisible(1)" x-cloak id="section-personal" class="scroll-mt-24">
                    <x-slot:title>{{ $sections[1]['title'] }}</x-slot:title>
                    <div class="grid gap-5 sm:grid-cols-3">
                        <x-ui.field :label="__('First name')" for="first_name" required>
                            <x-ui.input name="first_name" :value="$value('first_name')" autocomplete="off" data-rules="required" :data-label="__('First name')" maxlength="100" />
                        </x-ui.field>
                        <x-ui.field :label="__('Middle name')" for="middle_name"><x-ui.input name="middle_name" :value="$value('middle_name')" autocomplete="off" maxlength="100" /></x-ui.field>
                        <x-ui.field :label="__('Last name')" for="last_name"><x-ui.input name="last_name" :value="$value('last_name')" autocomplete="off" maxlength="100" /></x-ui.field>

                        <x-ui.field :label="__('Father’s name')" for="father_name" class="sm:col-span-2"><x-ui.input name="father_name" :value="$value('father_name')" autocomplete="off" maxlength="150" /></x-ui.field>
                        <x-ui.field :label="__('Date of birth')" for="date_of_birth">
                            <x-ui.input name="date_of_birth" type="date" :value="$value('date_of_birth')" :max="now()->subDay()->format('Y-m-d')" data-rules="past" :data-label="__('Date of birth')" />
                        </x-ui.field>

                        <x-ui.field :label="__('Gender')" for="gender" tag="fieldset">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach (Employee::GENDERS as $key => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="gender" value="{{ $key }}" class="peer sr-only" @checked($value('gender') === $key)>
                                        <span class="block rounded-control border border-line-strong px-3 py-1.5 text-sm text-ink-2 transition hover:border-ink-3 peer-checked:border-primary peer-checked:bg-primary-soft peer-checked:font-medium peer-checked:text-primary-ink peer-focus-visible:ring-2 peer-focus-visible:ring-primary">{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </x-ui.field>
                        <x-ui.field :label="__('Marital status')" for="marital_status">
                            <x-ui.select name="marital_status" :value="$value('marital_status')" :placeholder="__('Select')" :options="collect(Employee::MARITAL_STATUSES)->map(fn ($l) => __($l))->all()" />
                        </x-ui.field>
                        <div class="max-sm:hidden"></div>

                        <x-ui.field :label="__('Mobile number')" for="mobile" :hint="__('10 digits, without +91')">
                            <x-ui.input name="mobile" type="tel" inputmode="numeric" mono :value="$value('mobile')" x-mask="99999 99999" placeholder="98765 43210" data-rules="digits:10" :data-label="__('Mobile number')" />
                        </x-ui.field>
                        <x-ui.field :label="__('Email')" for="email" class="sm:col-span-2">
                            <x-ui.input name="email" type="email" :value="$value('email')" data-rules="email" :data-label="__('Email')" />
                        </x-ui.field>
                        <x-ui.field :label="__('Address')" for="address" class="sm:col-span-3">
                            <x-ui.textarea name="address" :value="$value('address')" rows="2" maxlength="1000" />
                        </x-ui.field>
                    </div>
                </x-ui.card>

                {{-- 3. Statutory --}}
                <x-ui.card data-step="2" x-show="isVisible(2)" x-cloak id="section-statutory" class="scroll-mt-24">
                    <x-slot:title>{{ $sections[2]['title'] }}</x-slot:title>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.field :label="__('Aadhaar number')" for="aadhaar_number" :hint="__('12 digits')">
                            <x-ui.input name="aadhaar_number" inputmode="numeric" mono :value="$value('aadhaar_number')" x-mask="9999 9999 9999" placeholder="0000 0000 0000" data-rules="digits:12" :data-label="__('Aadhaar number')" autocomplete="off" />
                        </x-ui.field>
                        <x-ui.field :label="__('PAN')" for="pan_number" :hint="__('Example: ABCDE1234F')">
                            <x-ui.input name="pan_number" mono class="uppercase" :value="$value('pan_number')" x-mask="aaaaa9999a" placeholder="ABCDE1234F" data-rules="pan" :data-label="__('PAN')" autocomplete="off" />
                        </x-ui.field>
                        <x-ui.field :label="__('UAN (PF)')" for="uan_number" :hint="__('12-digit Universal Account Number')">
                            <x-ui.input name="uan_number" inputmode="numeric" mono :value="$value('uan_number')" x-mask="999999999999" data-rules="digits:12" :data-label="__('UAN')" autocomplete="off" />
                        </x-ui.field>
                        <x-ui.field :label="__('ESIC number')" for="esic_number" :hint="__('10 to 17 digits')">
                            <x-ui.input name="esic_number" inputmode="numeric" mono :value="$value('esic_number')" x-mask="99999999999999999" data-rules="digits-between:10,17" :data-label="__('ESIC number')" autocomplete="off" />
                        </x-ui.field>
                    </div>
                </x-ui.card>

                {{-- 4. Bank --}}
                <x-ui.card data-step="3" x-show="isVisible(3)" x-cloak id="section-bank" class="scroll-mt-24">
                    <x-slot:title>{{ $sections[3]['title'] }}</x-slot:title>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.field :label="__('Bank name')" for="bank_name" class="sm:col-span-2">
                            <x-ui.input name="bank_name" :value="$value('bank_name')" maxlength="255" list="bank-names" />
                            <datalist id="bank-names">
                                @foreach (['State Bank of India', 'Bank of Baroda', 'HDFC Bank', 'ICICI Bank', 'Axis Bank', 'Punjab National Bank', 'Union Bank of India', 'Kotak Mahindra Bank', 'Bank of India', 'Canara Bank', 'IDBI Bank', 'Dena Gujarat Gramin Bank'] as $bank)<option value="{{ $bank }}">@endforeach
                            </datalist>
                        </x-ui.field>
                        <x-ui.field :label="__('Account number')" for="bank_account_number">
                            <x-ui.input name="bank_account_number" inputmode="numeric" mono :value="$value('bank_account_number')" data-rules="account" :data-label="__('Account number')" autocomplete="off" />
                        </x-ui.field>
                        <x-ui.field :label="__('IFSC code')" for="ifsc_code" :hint="__('11 characters, printed on the cheque book')">
                            <x-ui.input name="ifsc_code" mono class="uppercase" maxlength="11" :value="$value('ifsc_code')" placeholder="SBIN0001234" data-rules="ifsc" :data-label="__('IFSC code')" autocomplete="off" />
                        </x-ui.field>
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- Sticky action bar --}}
        <div class="app-main fixed inset-x-0 bottom-0 z-20 border-t border-line bg-surface/95 backdrop-blur">
            <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-2 px-4 py-3 sm:px-6 lg:px-8">
                <x-ui.button variant="ghost" :href="route($employee->status === Employee::SEPARATED ? 'employees.separated' : 'employees.index')">{{ __('Cancel') }}</x-ui.button>
                <span class="flex-1"></span>
                <template x-if="stepped">
                    <div class="flex items-center gap-2">
                        <x-ui.button variant="secondary" type="button" @click="back()" x-show="step > 0" icon="chevron-left">{{ __('Back') }}</x-ui.button>
                        <x-ui.button type="button" @click="next()" x-show="step < steps.length - 1" icon-right="chevron-right">{{ __('Next') }}</x-ui.button>
                    </div>
                </template>
                @if ($isNew)
                    <x-ui.button variant="secondary" name="after" value="new" x-show="!stepped || step === steps.length - 1" class="max-sm:hidden">{{ __('Save & add another') }}</x-ui.button>
                @endif
                <x-ui.button x-show="!stepped || step === steps.length - 1">{{ $isNew ? __('Add employee') : __('Save changes') }}</x-ui.button>
            </div>
        </div>
    </form>
</x-app-layout>
