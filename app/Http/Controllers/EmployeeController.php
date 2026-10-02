<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        return $this->listing(Employee::ACTIVE, __('Employee list'));
    }

    public function separated(): View
    {
        return $this->listing(Employee::SEPARATED, __('Separated employees'));
    }

    public function create(): View
    {
        return view('employees.form', $this->formData(new Employee([
            'status' => Employee::ACTIVE,
            'company_id' => request('company_id'),
        ])));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return redirect()->route('employees.index')->with('status', __('Employee :code added.', ['code' => $employee->employee_code]));
    }

    public function edit(Employee $employee): View
    {
        return view('employees.form', $this->formData($employee));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();
        $data['employee_code'] = $data['employee_code'] ?? $employee->employee_code;
        $employee->update($data);

        return redirect()->route($employee->status === Employee::SEPARATED ? 'employees.separated' : 'employees.index')
            ->with('status', __('Employee updated.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return back()->with('status', __('Employee deleted.'));
    }

    private function listing(string $status, string $title): View
    {
        $employees = Employee::with(['company', 'department', 'position'])->where('status', $status)
            ->when(request('company_id'), fn ($query, $id) => $query->where('company_id', $id))
            ->when(request('department_id'), fn ($query, $id) => $query->where('department_id', $id))
            ->when(request('search'), function ($query, $term) {
                $query->where(fn ($q) => $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('employee_code', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%"));
            })
            ->orderBy('company_id')->orderBy('employee_code')
            ->paginate(15)->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'title' => $title,
            'separatedView' => $status === Employee::SEPARATED,
            'companies' => Company::orderBy('establishment_name')->get(['id', 'establishment_name']),
            'departments' => Department::orderBy('name')->get(['id', 'company_id', 'name']),
        ]);
    }

    private function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'companies' => Company::orderBy('establishment_name')->get(['id', 'establishment_name']),
            'departments' => Department::orderBy('name')->get(['id', 'company_id', 'name']),
            'positions' => Position::orderBy('name')->get(['id', 'name']),
        ];
    }
}
