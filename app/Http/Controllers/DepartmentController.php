<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Requests\DepartmentRequest;
use App\Models\Company;
use App\Models\Department;
use App\Support\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    use ListsRecords;

    public function index(CurrentCompany $current): View
    {
        $companyId = $current->filter();
        $query = Department::with(['company', 'parent'])->withCount(['employees' => fn ($q) => $q->active()])
            ->when($companyId, fn ($query, $id) => $query->where('company_id', $id))
            ->when(request('search'), fn ($query, $term) => $query->where('name', 'like', "%{$term}%"));

        $departments = $this->sorted($query, [
            'name' => fn ($q, $dir) => $q->orderBy('company_id')->orderBy('name', $dir),
            'employees' => 'employees_count',
        ], 'name')->paginate($this->perPage())->withQueryString();

        return view('departments.index', [
            'departments' => $departments,
            'companyId' => $companyId,
        ]);
    }

    public function create(CurrentCompany $current): View
    {
        return view('departments.form', $this->formData(new Department(['company_id' => request('company_id', $current->id())])));
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

        return redirect()->route('departments.index')->with('status', __('Department deleted.'))
            ->with('undo', route('departments.restore', $department));
    }

    public function restore(Department $department): RedirectResponse
    {
        $department->restore();

        return back()->with('status', __('Department restored.'));
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
