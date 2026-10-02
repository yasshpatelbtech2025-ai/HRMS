<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $departments = Department::with(['company', 'parent'])->withCount('employees')
            ->when(request('company_id'), fn ($query, $id) => $query->where('company_id', $id))
            ->orderBy('company_id')->orderBy('name')
            ->paginate(15)->withQueryString();

        return view('departments.index', [
            'departments' => $departments,
            'companies' => Company::orderBy('establishment_name')->get(['id', 'establishment_name']),
        ]);
    }

    public function create(): View
    {
        return view('departments.form', $this->formData(new Department));
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()->route('departments.index')->with('status', __('Department added.'));
    }

    public function edit(Department $department): View
    {
        return view('departments.form', $this->formData($department));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('departments.index')->with('status', __('Department updated.'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->children()->exists() || $department->employees()->exists()) {
            return back()->with('error', __('This department still has sub-departments or employees.'));
        }

        $department->delete();

        return redirect()->route('departments.index')->with('status', __('Department deleted.'));
    }

    private function formData(Department $department): array
    {
        return [
            'department' => $department,
            'companies' => Company::orderBy('establishment_name')->get(['id', 'establishment_name']),
            'departments' => Department::orderBy('name')->get(['id', 'company_id', 'name']),
        ];
    }
}
