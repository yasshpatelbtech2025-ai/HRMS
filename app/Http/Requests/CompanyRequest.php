<?php

namespace App\Http\Requests;

use App\Rules\AccessibleCompany;
use App\Models\Company;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function rules(): array
    {
        $own = $this->route('company');

        return [
            'establishment_name' => ['required', 'string', 'max:255'],
            'parent_company_id' => [
                'nullable', 'integer',
                new AccessibleCompany('id'), Rule::exists('companies', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail) use ($own) {
                    if ($own && Company::find($value)?->isWithin($own)) {
                        $fail(__('A company cannot be connected to itself or to one of its own connected companies.'));
                    }
                },
            ],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
            'taluka' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['parent_company_id' => __('connected company')];
    }
}
