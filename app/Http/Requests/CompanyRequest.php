<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function rules(): array
    {
        $own = $this->route('company');

        return [
            'establishment_name' => ['required', 'string', 'max:255'],
            'parent_company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at'), Rule::notIn([$own?->id])],
            'organization_size' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
            'taluka' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
        ];
    }
}
