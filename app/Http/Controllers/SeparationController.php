<?php

namespace App\Http\Controllers;

use App\Rules\AccessibleEmployee;
use App\Http\Controllers\Concerns\ListsRecords;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Employee › Separation employees list tab: everyone with a separation
 * date, newest first. Separations can be added, edited and removed here
 * as well as from the Employee list.
 */
class SeparationController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        $employees = Employee::whereNotNull('separation_date')
            ->with(['company', 'department', 'subDepartment', 'position'])
            ->tap(fn (Builder $q) => $this->search($q, $request, ['employee_code', 'first_name', 'middle_name', 'last_name', 'phone']))
            ->tap(fn (Builder $q) => $this->sort($q, $request, ['code' => 'employee_code', 'name' => 'first_name', 'joined' => 'date_of_joining', 'separated' => 'separation_date'],
                fn (Builder $q) => $q->orderByDesc('separation_date')->orderBy('employee_code')))
            ->paginate($perPage = $this->perPage($request))->withQueryString();

        return view('employees.separations.index', [
            'employees' => $employees,
            'perPage' => $perPage,
            'candidates' => Employee::whereNull('separation_date')->with('company')->orderBy('company_id')->orderBy('employee_code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', new AccessibleEmployee('uuid'), Rule::exists('employees', 'uuid')->whereNull('deleted_at')->whereNull('separation_date')],
            'separation_date' => ['required', 'date'],
        ], ['employee.exists' => __('Choose an employee who is not already separated.')]);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();
        $this->separate($employee, $data['separation_date']);

        return back()->with('status', __('Separation added for :name.', ['name' => $employee->full_name]));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate(['separation_date' => ['required', 'date']]);
        $this->separate($employee, $data['separation_date']);

        return back()->with('status', __('Separation date for :name updated.', ['name' => $employee->full_name]));
    }

    /**
     * Remove the separation, returning the employee to the Employee list.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->update(['separation_date' => null]);

        return back()->with('status', __(':name is back on the employee list.', ['name' => $employee->full_name]));
    }

    private function separate(Employee $employee, string $date): void
    {
        if ($employee->date_of_joining && $employee->date_of_joining->gt($date)) {
            throw ValidationException::withMessages([
                'separation_date' => __('The separation date cannot be before :name joined (:date).', ['name' => $employee->full_name, 'date' => $employee->date_of_joining->format('d M Y')]),
            ]);
        }

        $employee->update(['separation_date' => $date]);
    }
}
