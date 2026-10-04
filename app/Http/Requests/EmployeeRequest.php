<?php

namespace App\Http\Requests;

use App\Rules\AccessibleCompany;
use App\Rules\AccessibleEmployee;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\PayrollSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The Add / Edit employee form. Field names follow the old HRMS form;
 * repeating groups (addresses, qualifications, experience, family, salary)
 * are nested arrays.
 */
class EmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $digits = fn ($value) => is_string($value) ? preg_replace('/\s+/', '', $value) : $value;
        $upper = fn ($value) => is_string($value) ? strtoupper(trim($value)) : $value;

        $this->merge([
            'aadhar_card_no' => $digits($this->input('aadhar_card_no')),
            'account_number' => $digits($this->input('account_number')),
            'pan_card_no' => $upper($this->input('pan_card_no')),
            'ifsc_code' => $upper($this->input('ifsc_code')),
            'permanent_address_is_present' => $this->boolean('permanent_address_is_present'),
            'is_disabled' => $this->boolean('is_disabled'),
            'family' => collect($this->input('family', []))
                ->map(fn ($member) => is_array($member) ? ['aadhar_card_no' => $digits($member['aadhar_card_no'] ?? null)] + $member : $member)
                ->all(),
        ]);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $companyId = $this->integer('company_id');
        $money = ['nullable', 'numeric', 'min:0', 'max:9999999999'];
        $text = ['nullable', 'string', 'max:255'];
        $document = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        $rules = [
            // Basic info
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => $text,
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee)],
            'phone' => ['required', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'marital_status' => ['nullable', Rule::in(array_keys(config('employee.marital_statuses')))],
            'religion' => $text,
            'caste' => $text,
            'no_of_kids' => ['nullable', 'integer', 'min:0', 'max:30'],
            'country' => ['required', Rule::in(config('employee.countries'))],

            // Employee details
            'company_id' => ['required', 'integer', new AccessibleCompany('id'), Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'shift_id' => ['required', 'integer', Rule::exists('shifts', 'id')->where('company_id', $companyId)],
            'date_of_joining' => ['required', 'date'],
            'works_on_hazardous_process' => ['required', 'boolean'],
            'can_punch_in_hybrid_mode' => ['required', 'boolean'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'sub_department_id' => ['required', 'integer', Rule::exists('sub_departments', 'id')->where('department_id', $this->integer('department_id'))],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')->where('company_id', $companyId)],
            'working_place' => ['required', Rule::in(array_keys(config('employee.working_places')))],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'reporting_officer_id' => [
                'nullable', 'integer', Rule::notIn([$employee?->id]),
                new AccessibleEmployee('id'), Rule::exists('employees', 'id')->where('company_id', $companyId)->whereNull('deleted_at'),
            ],

            // Addresses
            'permanent_address_is_present' => ['boolean'],
            'addresses' => ['nullable', 'array:'.implode(',', array_keys(config('employee.address_types')))],

            // Documents
            'aadhar_card_no' => ['nullable', 'digits:12'],
            'pan_card_no' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'passport_no' => ['nullable', 'string', 'max:20'],
            'driving_license_no' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'photo_data' => ['nullable', 'string', 'max:2000000', 'regex:/^data:image\/(jpeg|png|webp);base64,[A-Za-z0-9+\/=]+$/'],
            'aadhar_attachment' => $document,
            'pan_attachment' => $document,

            // Bank
            'bank_name' => $text,
            'branch_address' => $text,
            'account_number' => ['nullable', 'regex:/^[0-9]{6,20}$/'],
            'bank_passbook' => $document,
            'micr_code' => ['nullable', 'digits:9'],
            'ifsc_code' => ['nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'uan_code' => ['nullable', 'digits:12'],
            'name_as_per_uan' => $text,
            'ip_number' => ['nullable', 'string', 'max:20'],
            'name_as_per_esic' => $text,

            // Medical
            'blood_group' => ['nullable', Rule::in(config('employee.blood_groups'))],
            'health_condition' => $text,
            'is_disabled' => ['nullable', 'boolean'],
            'disability_description' => ['nullable', 'required_if:is_disabled,1', 'string', 'max:255'],

            // Qualification and experience
            'qualifications' => ['nullable', 'array:'.implode(',', array_keys(config('employee.qualification_levels')))],
            'qualifications.*.school' => $text,
            'qualifications.*.tenure' => ['nullable', 'string', 'max:50'],
            'qualifications.*.passing_year' => ['nullable', 'string', 'max:10'],
            'qualifications.*.percentage' => ['nullable', 'string', 'max:20'],
            'job_status' => ['nullable', Rule::in(array_keys(config('employee.job_statuses')))],
            'experiences' => ['nullable', 'array', 'max:20'],
            'experiences.*.company_name' => $text,
            'experiences.*.designation' => $text,
            'experiences.*.salary' => ['nullable', 'string', 'max:50'],
            'experiences.*.total_years_worked' => ['nullable', 'string', 'max:20'],
            'experiences.*.reason_of_leaving' => $text,
            'date_of_exit_prev_org' => ['nullable', 'date', 'before_or_equal:date_of_joining'],

            // Salary info
            'pay_frequency' => ['required', Rule::in(array_keys(config('employee.pay_frequencies')))],
            'hourly_rate' => ['nullable', 'required_if:pay_frequency,hourly', ...array_slice($money, 1)],
            'daily_rate' => ['nullable', 'required_if:pay_frequency,daily', ...array_slice($money, 1)],
            'daily_hour' => ['required', 'numeric', 'min:0', 'max:24'],
            'tax_regime' => ['required', Rule::in(array_keys(config('employee.tax_regimes')))],
            'monthly_salary_calculation' => ['required', Rule::in(array_keys(config('employee.monthly_salary_calculations')))],
            'weekly_leave_day' => ['required', Rule::in(array_keys(config('employee.weekdays')))],
            'salary' => ['nullable', 'array:'.implode(',', array_keys($this->salaryElements()))],
            'salary.basic.monthly' => ['nullable', 'required_if:pay_frequency,monthly', ...array_slice($money, 1)],

            // Family
            'family' => ['nullable', 'array:'.implode(',', array_keys(config('employee.family_relations')))],
            'family.*.full_name' => $text,
            'family.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'family.*.aadhar_card_no' => ['nullable', 'digits:12'],
            'family.*.is_nominee' => ['nullable', 'boolean'],

            'nfc_card_no' => ['nullable', 'string', 'max:50'],
        ];

        foreach (array_keys(config('employee.address_types')) as $type) {
            foreach (['address_line_1', 'address_line_2', 'village', 'district', 'taluka', 'state'] as $field) {
                $rules["addresses.{$type}.{$field}"] = $text;
            }
        }

        foreach (array_keys($this->salaryElements()) as $element) {
            $rules["salary.{$element}.monthly"] ??= $money;
            $rules["salary.{$element}.yearly"] = $money;
        }

        return $rules;
    }

    /**
     * A "New Structure" company (Configs › Payroll) only pays basic,
     * allowance and bonus, with basic at least half of gross.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            $structure = PayrollSetting::where('company_id', $this->integer('company_id'))->value('salary_structure');

            if ($structure !== 'new') {
                return;
            }

            foreach (array_diff(array_keys(config('employee.gross_elements')), ['basic', 'allowance', 'bonus']) as $element) {
                if ((float) $this->input("salary.{$element}.monthly") > 0 || (float) $this->input("salary.{$element}.yearly") > 0) {
                    $validator->errors()->add("salary.{$element}.monthly", __('This company uses the New Structure, which only has Basic, Allowance and Bonus.'));
                }
            }

            if ((float) $this->input('salary.basic.monthly') < (float) $this->input('salary.allowance.monthly')) {
                $validator->errors()->add('salary.basic.monthly', __('Basic Salary must be greater than 50% of Gross Salary.'));
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'department_id' => 'department',
            'shift_id' => 'shift',
            'sub_department_id' => 'sub department',
            'position_id' => 'designation',
            'reporting_officer_id' => 'reporting officer',
            'date_of_joining' => 'joining date',
            'works_on_hazardous_process' => 'working on hazardous process',
            'aadhar_card_no' => 'Aadhar card no',
            'pan_card_no' => 'PAN card no',
            'ifsc_code' => 'IFSC code',
            'micr_code' => 'MICR code',
            'uan_code' => 'UAN code',
            'salary.basic.monthly' => 'basic (monthly)',
            'family.*.aadhar_card_no' => 'Aadhar card no',
        ];
    }

    public function messages(): array
    {
        return [
            'pan_card_no.regex' => 'The PAN card no must look like ABCDE1234F.',
            'ifsc_code.regex' => 'The IFSC code must look like SBIN0001234.',
            'account_number.regex' => 'The account number must be 6 to 20 digits.',
            'photo_data.regex' => 'The captured photo could not be read. Please capture or choose it again.',
        ];
    }

    /**
     * @return array<string, string> salary element key => label
     */
    private function salaryElements(): array
    {
        return config('employee.gross_elements') + config('employee.deduction_elements');
    }
}
