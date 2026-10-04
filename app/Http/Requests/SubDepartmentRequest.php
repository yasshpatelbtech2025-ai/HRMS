<?php

namespace App\Http\Requests;

use App\Rules\AccessibleCompany;
use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubDepartmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'department' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'integer', new AccessibleCompany('id'), Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['company_id' => __('company'), 'name' => __('sub department name')];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || ! ($department = $this->existingDepartment())) {
                    return;
                }

                $taken = SubDepartment::where('department_id', $department->id)
                    ->whereRaw('lower(name) = ?', [mb_strtolower(trim($this->input('name')))])
                    ->when($this->route('subDepartment'), fn ($q, $own) => $q->whereKeyNot($own->id))
                    ->exists();

                if ($taken) {
                    $validator->errors()->add('name', __('This department already has a sub department with that name.'));
                }
            },
        ];
    }

    /**
     * The chosen company's department with the typed name, if it exists yet.
     */
    public function existingDepartment(): ?Department
    {
        return Department::where('company_id', $this->integer('company_id'))
            ->whereRaw('lower(name) = ?', [mb_strtolower(trim($this->input('department')))])
            ->first();
    }

    /**
     * The department to file the sub department under, created if new.
     */
    public function department(): Department
    {
        return $this->existingDepartment()
            ?? Department::forceCreate(['company_id' => $this->integer('company_id'), 'name' => trim($this->input('department'))]);
    }
}
