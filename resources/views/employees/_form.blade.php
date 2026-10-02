@php
    $field = fn (string $name) => old($name, $employee->{$name} instanceof \BackedEnum ? $employee->{$name}->value : $employee->{$name});
    $date = fn (string $name) => old($name, $employee->{$name}?->format('Y-m-d'));
    $select = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<div class="space-y-6">
    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <h3 class="text-base font-semibold text-gray-900">{{ __('Personal details') }}</h3>
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="first_name" :value="__('First name')" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="$field('first_name')" required autofocus />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="last_name" :value="__('Last name')" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="$field('last_name')" required />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="$field('email')" required />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="$field('phone')" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
            <div>
                <x-input-label for="gender" :value="__('Gender')" />
                <select id="gender" name="gender" class="{{ $select }}">
                    <option value="">{{ __('Not specified') }}</option>
                    @foreach (\App\Enums\Gender::cases() as $gender)
                        <option value="{{ $gender->value }}" @selected($field('gender') === $gender->value)>{{ __($gender->label()) }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('gender')" />
            </div>
            <div>
                <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full" :value="$date('date_of_birth')" />
                <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="address" :value="__('Address')" />
                <textarea id="address" name="address" rows="3" class="{{ $select }}">{{ $field('address') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('address')" />
            </div>
        </div>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <h3 class="text-base font-semibold text-gray-900">{{ __('Employment') }}</h3>
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            {{-- The department list follows the chosen company. --}}
            <div class="contents"
                 x-data="{ companyId: @js((string) $field('company_id')), departmentId: @js((string) $field('department_id')), departments: @js($companies->mapWithKeys(fn ($c) => [$c->id => $c->departments->map->only('id', 'name')])) }">
                <div>
                    <x-input-label for="company_id" :value="__('Company')" />
                    <select id="company_id" name="company_id" class="{{ $select }}" x-model="companyId" @change="departmentId = ''" required>
                        <option value="">{{ __('Select a company') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) $field('company_id') === (string) $company->id)>{{ $company->establishment_name }}</option>
                        @endforeach
                    </select>
                    @if ($companies->isEmpty())
                        <p class="mt-2 text-sm text-gray-500">{!! __('No companies yet. :link first.', ['link' => '<a href="'.route('company.create').'" class="font-medium text-brand-700 hover:text-brand-600">'.e(__('Add a company')).'</a>']) !!}</p>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('company_id')" />
                </div>
                <div>
                    <x-input-label for="department_id" :value="__('Department')" />
                    <select id="department_id" name="department_id" class="{{ $select }}" x-model="departmentId" :disabled="!companyId">
                        <option value="">{{ __('No department') }}</option>
                        <template x-for="department in departments[companyId] ?? []" :key="department.id">
                            <option :value="String(department.id)" x-text="department.name" :selected="String(department.id) === departmentId"></option>
                        </template>
                    </select>
                    <p class="mt-2 text-sm text-gray-500" x-show="companyId && !(departments[companyId] ?? []).length" style="display: none;">
                        {{ __('This company has no departments yet. Add them from the company\'s edit page.') }}
                    </p>
                    <x-input-error class="mt-2" :messages="$errors->get('department_id')" />
                </div>
            </div>
            <div>
                <x-input-label for="employee_code" :value="__('Employee code')" />
                <x-text-input id="employee_code" name="employee_code" type="text" class="mt-1 block w-full" :value="$field('employee_code')" required />
                <x-input-error class="mt-2" :messages="$errors->get('employee_code')" />
            </div>
            <div>
                <x-input-label for="date_of_joining" :value="__('Date of joining')" />
                <x-text-input id="date_of_joining" name="date_of_joining" type="date" class="mt-1 block w-full" :value="$date('date_of_joining')" required />
                <x-input-error class="mt-2" :messages="$errors->get('date_of_joining')" />
            </div>
            <div>
                <x-input-label for="designation" :value="__('Designation')" />
                <x-text-input id="designation" name="designation" type="text" class="mt-1 block w-full" :value="$field('designation')" />
                <x-input-error class="mt-2" :messages="$errors->get('designation')" />
            </div>
            <div>
                <x-input-label for="employment_type" :value="__('Employment type')" />
                <select id="employment_type" name="employment_type" class="{{ $select }}" required>
                    @foreach (\App\Enums\EmploymentType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(($field('employment_type') ?? 'full_time') === $type->value)>{{ __($type->label()) }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('employment_type')" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="{{ $select }}" required>
                    @foreach (\App\Enums\EmployeeStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($field('status') ?? 'active') === $status->value)>{{ __($status->label()) }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('status')" />
            </div>
        </div>
    </section>
</div>
