<?php

namespace App\Http\Controllers;

use App\Enums\EmployeeStatus;
use App\Http\Requests\EmployeeRequest;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],
            'company_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
        ]);

        $employees = Employee::with(['company', 'department'])
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['company_id'] ?? null, fn ($q, $id) => $q->where('company_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'filters' => $filters,
            'companies' => $this->companiesWithDepartments(),
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'employee' => new Employee([
                'employee_code' => Employee::nextCode(),
                'date_of_joining' => today(),
            ]),
            'companies' => $this->companiesWithDepartments(),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return redirect()->route('employees.show', $employee)->with('status', __('Employee added.'));
    }

    public function show(Employee $employee): View
    {
        return view('employees.show', ['employee' => $employee->load(['company', 'department'])]);
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            'companies' => $this->companiesWithDepartments(),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('employees.show', $employee)->with('status', __('Employee updated.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('status', __('Employee removed.'));
    }

    /**
     * @return Collection<int, Company>
     */
    private function companiesWithDepartments(): Collection
    {
        return Company::with('departments:id,company_id,name')->orderBy('establishment_name')->get(['id', 'establishment_name']);
    }
}
