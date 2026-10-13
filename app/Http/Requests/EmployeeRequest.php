<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Masked inputs show spaces for readability (98765 43210, 1234 5678 9012); store digits only.
        foreach (['mobile', 'aadhaar_number', 'uan_number', 'esic_number', 'bank_account_number'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => preg_replace('/[\s-]+/', '', (string) $this->input($field))]);
            }
        }

        $this->merge([
            'pan_number' => $this->filled('pan_number') ? strtoupper(trim($this->pan_number)) : null,
            'ifsc_code' => $this->filled('ifsc_code') ? strtoupper(trim($this->ifsc_code)) : null,
        ]);
    }

    public function rules(): array
    {
        $own = $this->route('employee');
        $company = $this->input('company_id');

        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'employee_code' => ['nullable', 'string', 'max:20', Rule::unique('employees', 'employee_code')->where('company_id', $company)->ignore($own?->id)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('company_id', $company)->whereNull('deleted_at')],
            'position_id' => ['nullable', 'integer', Rule::exists('positions', 'id')->whereNull('deleted_at')],

            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'gender' => ['nullable', Rule::in(array_keys(Employee::GENDERS))],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'marital_status' => ['nullable', Rule::in(array_keys(Employee::MARITAL_STATUSES))],
            'mobile' => ['nullable', 'digits:10'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],

            'joining_date' => ['required', 'date'],
            'status' => ['required', Rule::in([Employee::ACTIVE, Employee::SEPARATED])],
            'separation_date' => ['nullable', 'required_if:status,'.Employee::SEPARATED, 'date', 'after_or_equal:joining_date'],
            'separation_reason' => ['nullable', 'string', 'max:255'],
            'gross_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],

            'aadhaar_number' => ['nullable', 'digits:12'],
            'pan_number' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'uan_number' => ['nullable', 'digits:12'],
            'esic_number' => ['nullable', 'digits_between:10,17'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'regex:/^[0-9]{6,34}$/'],
            'ifsc_code' => ['nullable', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'pan_number.regex' => 'PAN must look like ABCDE1234F.',
            'ifsc_code.regex' => 'IFSC must look like SBIN0001234.',
            'bank_account_number.regex' => 'Account number must be 6 to 34 digits.',
        ];
    }
}
