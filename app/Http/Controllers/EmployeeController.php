<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Requests\EmployeeRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Support\CurrentCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use ListsRecords;

    /** Dashboard "needs attention" filters: ?missing=aadhaar etc. */
    public const MISSING = [
        'aadhaar' => 'Missing Aadhaar',
        'uan' => 'Missing UAN',
        'bank' => 'Missing bank details',
        'mobile' => 'Missing mobile',
    ];

    public function index(CurrentCompany $current): View
    {
        return $this->listing(Employee::ACTIVE, __('Employees'), $current);
    }

    public function separated(CurrentCompany $current): View
    {
        return $this->listing(Employee::SEPARATED, __('Separated employees'), $current);
    }

    public function create(CurrentCompany $current): View
    {
        return view('employees.form', $this->formData(new Employee([
            'status' => Employee::ACTIVE,
            'company_id' => request('company_id', $current->id()),
            'joining_date' => now(),
        ])));
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());
        $message = __(':name added as :code.', ['name' => $employee->full_name, 'code' => $employee->employee_code]);

        if ($request->input('after') === 'new') {
            return redirect()->route('employees.create', ['company_id' => $employee->company_id])->with('status', $message);
        }

        return redirect()->route('employees.index')->with('status', $message);
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

        return back()->with('status', __(':name deleted.', ['name' => $employee->full_name]))
            ->with('undo', route('employees.restore', $employee));
    }

    public function restore(Employee $employee): RedirectResponse
    {
        $employee->restore();

        return back()->with('status', __(':name restored.', ['name' => $employee->full_name]));
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $uuids = $request->validate(['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['string']])['ids'];
        $count = Employee::whereIn('uuid', $uuids)->get()->each->delete()->count();

        return back()->with('status', trans_choice(':count employee deleted.|:count employees deleted.', $count, ['count' => $count]))
            ->with('undo', route('employees.bulk-restore', ['ids' => implode(',', $uuids)]));
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $uuids = array_filter(explode(',', (string) $request->query('ids')));
        $count = Employee::onlyTrashed()->whereIn('uuid', $uuids)->get()->each->restore()->count();

        return back()->with('status', trans_choice(':count employee restored.|:count employees restored.', $count, ['count' => $count]));
    }

    private function listing(string $status, string $title, CurrentCompany $current): View
    {
        $companyId = $current->filter();

        $query = Employee::with(['company', 'department', 'position'])->where('status', $status)
            ->when($companyId, fn ($query, $id) => $query->where('company_id', $id))
            ->when(request('department_id'), fn ($query, $id) => $query->where('department_id', $id))
            ->when(request('position_id'), fn ($query, $id) => $query->where('position_id', $id))
            ->when(request('missing'), fn ($query, $field) => match ($field) {
                'aadhaar' => $query->whereNull('aadhaar_number'),
                'uan' => $query->whereNull('uan_number'),
                'bank' => $query->where(fn ($q) => $q->whereNull('bank_account_number')->orWhereNull('ifsc_code')),
                'mobile' => $query->whereNull('mobile'),
                default => $query,
            })
            ->when(request('search'), function ($query, $term) {
                $query->where(fn ($q) => $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('employee_code', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%"));
            });

        $employees = $this->sorted($query, [
            'code' => fn ($q, $dir) => $q->orderBy('company_id')->orderBy('employee_code', $dir),
            'name' => fn ($q, $dir) => $q->orderBy('first_name', $dir)->orderBy('last_name', $dir),
            'joining_date' => 'joining_date',
            'separation_date' => 'separation_date',
            'salary' => 'gross_salary',
        ], 'code')->paginate($this->perPage())->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'title' => $title,
            'separatedView' => $status === Employee::SEPARATED,
            'companyId' => $companyId,
            'companies' => Company::orderBy('establishment_name')->get(['id', 'establishment_name']),
            'departments' => Department::orderBy('name')->get(['id', 'company_id', 'name']),
            'positions' => Position::orderBy('name')->get(['id', 'name']),
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
