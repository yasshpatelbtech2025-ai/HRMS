<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function rules(): array
    {
        $own = $this->route('department');

        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('departments', 'id')->where('company_id', $this->input('company_id'))->whereNull('deleted_at'),
                Rule::notIn([$own?->id]),
            ],
        ];
    }
}
