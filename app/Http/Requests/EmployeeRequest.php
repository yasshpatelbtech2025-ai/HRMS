<?php

namespace App\Http\Requests;

use App\Rules\AccessibleCompany;
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
            'pay_frequency' => $this->input('pay_frequency') ?: 'monthly',
            'tax_regime' => $this->input('tax_regime') ?: 'new',
            'family' => collect($this->input('family', []))
                ->map(fn ($member) => is_array($member) ? ['aadhar_card_no' => $digits($member['aadhar_card_no'] ?? null)] + $member : $member)
                ->all(),
        ]);
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $money = ['nullable', 'numeric', 'min:0', 'max:9999999999'];
        $text = ['nullable', 'string', 'max:255'];
        $document = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        $statutory = $employee ? 'nullable' : 'required';

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
            'country' => ['required', Rule::in(config('employee.countries'))],
            'company_id' => ['required', 'integer', new AccessibleCompany('id'), Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'date_of_joining' => ['required', 'date'],

            // Addresses
            'permanent_address_is_present' => ['boolean'],
            'addresses' => ['nullable', 'array:'.implode(',', array_keys(config('employee.address_types')))],

            // Documents
            'passport_no' => ['nullable', 'string', 'max:20'],
            'driving_license_no' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'photo_data' => ['nullable', 'string', 'max:2000000', 'regex:/^data:image\/(jpeg|png|webp);base64,[A-Za-z0-9+\/=]+$/',
                // The bytes themselves must be a picture, not just labelled as one.
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (\App\Actions\SaveEmployee::photoExtension((string) $value) === null) {
                        $fail(__('The captured photo could not be read. Please capture or choose it again.'));
                    }
                }],
            'aadhar_attachment' => $document,
            'pan_attachment' => $document,

            // Statutory: every field is mandatory when adding an employee.
            'aadhar_card_no' => [$statutory, 'digits:12'],
            'pan_card_no' => [$statutory, 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'uan_code' => [$statutory, 'digits:12'],
            'uan_document' => [$statutory, ...array_slice($document, 1)],
            'ip_number' => [$statutory, 'string', 'max:20'],
            'nominee_name' => [$statutory, ...array_slice($text, 1)],
            'nominee_relationship' => [$statutory, 'string', 'max:50'],
            'nominee_document' => [$statutory, ...array_slice($document, 1)],

            // Bank
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'regex:/^[0-9]{6,20}$/'],
            'ifsc_code' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'branch_address' => $text,
            'micr_code' => ['nullable', 'digits:9'],
            'bank_passbook' => $document,

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

            // Salary info: nothing is mandatory (blank pay frequency / tax regime fall back to their defaults).
            'pay_frequency' => ['nullable', Rule::in(array_keys(config('employee.pay_frequencies')))],
            'hourly_rate' => $money,
            'daily_rate' => $money,
            'daily_hour' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'tax_regime' => ['nullable', Rule::in(array_keys(config('employee.tax_regimes')))],
            'monthly_salary_calculation' => ['nullable', Rule::in(array_keys(config('employee.monthly_salary_calculations')))],
            'weekly_leave_day' => ['nullable', Rule::in(array_keys(config('employee.weekdays')))],
            'salary' => ['nullable', 'array:'.implode(',', array_keys($this->salaryElements()))],

            // Family
            'family' => ['nullable', 'array:'.implode(',', array_keys(config('employee.family_relations')))],
            'family.*.full_name' => $text,
            'family.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'family.*.aadhar_card_no' => ['nullable', 'digits:12'],

            'nfc_card_no' => ['nullable', 'string', 'max:50'],
        ];

        foreach (array_keys(config('employee.address_types')) as $type) {
            foreach (['address_line_1', 'address_line_2', 'city', 'village', 'taluka', 'district', 'state', 'country'] as $field) {
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
     * allowance and bonus.
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
        }];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'date_of_joining' => 'joining date',
            'aadhar_card_no' => 'Aadhar no',
            'pan_card_no' => 'PAN no',
            'ifsc_code' => 'IFSC code',
            'micr_code' => 'MICR code',
            'uan_code' => 'UAN no',
            'ip_number' => 'ESIC no',
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
