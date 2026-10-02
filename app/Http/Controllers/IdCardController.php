<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Employee › Employee id card tab: current employees, with a printable
 * card for each and the date a card was handed over.
 */
class IdCardController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        $employees = Employee::current()->with('position')
            ->tap(fn (Builder $q) => $this->search($q, $request, ['employee_code', 'first_name', 'middle_name', 'last_name', 'phone', 'position.name']))
            ->orderBy('company_id')->orderBy('employee_code')
            ->paginate($perPage = $this->perPage($request))->withQueryString();

        return view('employees.id-cards.index', [
            'employees' => $employees,
            'perPage' => $perPage,
            'unassigned' => Employee::current()->whereNull('id_card_assigned_on')->with('company')->orderBy('company_id')->orderBy('employee_code')->get(),
        ]);
    }

    public function show(Employee $employee): View
    {
        $employee->load(['company', 'position', 'subDepartment', 'department']);

        return view('employees.id-cards.show', compact('employee'));
    }

    /**
     * "Assign id card": record the date a card was handed to an employee.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee' => ['required', 'uuid', Rule::exists('employees', 'uuid')->whereNull('deleted_at')->whereNull('id_card_assigned_on')],
            'assigned_on' => ['required', 'date', 'before_or_equal:today'],
        ], ['employee.exists' => __('Choose an employee who does not have an id card yet.')]);

        $employee = Employee::where('uuid', $data['employee'])->firstOrFail();
        $this->assign($employee, $data['assigned_on']);

        return back()->with('status', __('ID card assigned to :name.', ['name' => $employee->full_name]));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate(['assigned_on' => ['required', 'date', 'before_or_equal:today']]);
        $this->assign($employee, $data['assigned_on']);

        return back()->with('status', __('ID card assign date for :name updated.', ['name' => $employee->full_name]));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->update(['id_card_assigned_on' => null]);

        return back()->with('status', __('ID card assignment for :name removed.', ['name' => $employee->full_name]));
    }

    /**
     * "Mark as assigned today" on the printable card.
     */
    public function assignToday(Employee $employee): RedirectResponse
    {
        $employee->id_card_assigned_on ??= today();
        $employee->save();

        return back()->with('status', __('ID card assigned to :name on :date.', [
            'name' => $employee->full_name, 'date' => $employee->id_card_assigned_on->format('d M Y'),
        ]));
    }

    private function assign(Employee $employee, string $date): void
    {
        if ($employee->date_of_joining && $employee->date_of_joining->gt($date)) {
            throw ValidationException::withMessages([
                'assigned_on' => __('The assign date cannot be before :name joined (:date).', ['name' => $employee->full_name, 'date' => $employee->date_of_joining->format('d M Y')]),
            ]);
        }

        $employee->update(['id_card_assigned_on' => $date]);
    }
}
