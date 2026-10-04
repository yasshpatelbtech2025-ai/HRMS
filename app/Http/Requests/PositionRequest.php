<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    public function rules(): array
    {
        $position = $this->route('position');
        // A position's company is fixed once created, as in the old system.
        $companyId = $position?->company_id ?? $this->integer('company_id');

        return [
            'company_id' => $position ? ['prohibited'] : ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255', Rule::unique('positions')->where('company_id', $companyId)->whereNull('deleted_at')->ignore($position)],
            'details' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['company_id' => __('company'), 'name' => __('position name'), 'details' => __('position details')];
    }

    public function messages(): array
    {
        return ['name.unique' => __('This company already has a position with that name.')];
    }
}
