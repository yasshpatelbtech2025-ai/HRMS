<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollSetting;
use App\Models\SalaryRevision;
use App\Services\Payroll\SalaryRevisions;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Payroll › Salary revision: change an employee's salary structure from an
 * effective month. Revisions take effect once that month starts.
 */
class SalaryRevisionController extends Controller
{
    public function __construct(private SalaryRevisions $revisions) {}

    public function index(Request $request): View
    {
        $this->revisions->applyDue();

        $employees = Employee::current()->orderBy('company_id')->orderBy('employee_code')->get();
        $employee = $employees->firstWhere('uuid', $request->query('employee'));

        return view('payroll.revisions.index', [
            'employees' => $employees,
            'employee' => $employee,
            'revisions' => SalaryRevision::with('employee')
                ->when($employee, fn ($q) => $q->where('employee_id', $employee->id))
                ->latest('effective_from')->latest('id')->paginate(25)->withQueryString(),
        ]);
    }

    public function create(Employee $employee): View
    {
        return view('payroll.revisions.create', [
            'employee' => $employee->load('company'),
            'current' => $this->revisions->elementsOf($employee),
            'newStructure' => PayrollSetting::where('company_id', $employee->company_id)->value('salary_structure') === 'new',
        ]);
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $elements = array_keys(config('employee.gross_elements') + config('employee.deduction_elements'));
        $rules = ['effective_from' => ['required', 'date_format:Y-m'], 'remarks' => ['nullable', 'string', 'max:1000'], 'salary' => ['required', 'array']];
        foreach ($elements as $element) {
            $rules["salary.{$element}.monthly"] = ['nullable', 'numeric', 'min:0', 'max:9999999999'];
            $rules["salary.{$element}.yearly"] = ['nullable', 'numeric', 'min:0', 'max:9999999999'];
        }
        $data = $request->validate($rules, [], ['effective_from' => 'effective from']);

        $revised = collect($elements)->mapWithKeys(function ($element) use ($data) {
            $monthly = (float) ($data['salary'][$element]['monthly'] ?? 0);
            $yearly = $element === 'bonus' ? 0 : (float) ($data['salary'][$element]['yearly'] ?? round($monthly * 12, 2));

            return [$element => ['monthly' => $monthly, 'yearly' => $yearly]];
        })->filter(fn ($a) => $a['monthly'] > 0 || $a['yearly'] > 0)->all();

        if (empty($revised['basic']['monthly'])) {
            throw ValidationException::withMessages(['salary.basic.monthly' => __('Enter the revised basic salary.')]);
        }

        $previous = $this->revisions->elementsOf($employee);

        $revision = SalaryRevision::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'revised_on' => today(),
            'effective_from' => $data['effective_from'].'-01',
            'previous_yearly' => $this->revisions->yearlyGross($previous),
            'revised_yearly' => $this->revisions->yearlyGross($revised),
            'previous_elements' => $previous,
            'revised_elements' => $revised,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $applied = $this->revisions->applyDue() > 0 && $revision->fresh()->status === 'applied';

        return redirect()->route('payroll.revisions', ['employee' => $employee->uuid])->with('status', $applied
            ? __('Salary revised for :name and applied.', ['name' => $employee->full_name])
            : __('Salary revision for :name saved; it applies from :month.', ['name' => $employee->full_name, 'month' => CarbonImmutable::parse($revision->effective_from)->format('F Y')]));
    }

    public function apply(SalaryRevision $revision): RedirectResponse
    {
        abort_unless($revision->status === 'pending', 404);
        $this->revisions->apply($revision);

        return back()->with('status', __('Salary revision applied for :name.', ['name' => $revision->employee->full_name]));
    }

    public function cancel(SalaryRevision $revision): RedirectResponse
    {
        abort_unless($revision->status === 'pending', 404);
        $revision->update(['status' => 'cancelled']);

        return back()->with('status', __('Salary revision cancelled.'));
    }
}
