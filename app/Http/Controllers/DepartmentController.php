<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function store(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validateWithBag('department', [
            'name' => ['required', 'string', 'max:100', Rule::unique('departments')->where('company_id', $company->id)],
        ]);

        $company->departments()->create($validated);

        return redirect()->route('company.edit', $company)->withFragment('departments')->with('status', __('Department added.'));
    }

    public function destroy(Company $company, Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return back()->with('error', __('This department has employees. Move them to another department first.'));
        }

        $department->delete();

        return redirect()->route('company.edit', $company)->withFragment('departments')->with('status', __('Department removed.'));
    }
}
