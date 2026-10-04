<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Payroll\SalaryRevisions;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll › Current Salary: each current employee's salary type, rates and
 * main salary elements for a company.
 */
class CurrentSalaryController extends Controller
{
    use ListsRecords;

    public function export(Request $request, string $format, SalaryRevisions $revisions): StreamedResponse
    {
        $company = Company::where('uuid', $request->query('company'))->first() ?? Company::orderBy('establishment_name')->firstOrFail();
        $types = config('employee.pay_frequencies');

        return TableExport::download($format, 'current-salary',
            ['Card No', 'Employee Name', 'Designation', 'Salary Type', 'Daily Rate', 'Basic', 'HRA', 'Conveyance', 'Bonus %', 'CTC'],
            Employee::current()->where('company_id', $company->id)->with(['position', 'salaryElements'])->orderBy('employee_code')->get()
                ->map(function (Employee $e) use ($types, $revisions) {
                    $el = $e->salaryElements->keyBy('element');

                    return [
                        $e->employee_code, $e->full_name, $e->position?->name, $types[$e->pay_frequency] ?? '',
                        $e->daily_rate ?? ($e->hourly_rate ? $e->hourly_rate * ($e->daily_hour ?: 8) : null),
                        $el['basic']->monthly ?? null, $el['hra']->monthly ?? null, $el['conveyance']->monthly ?? null, $el['bonus']->monthly ?? null,
                        $revisions->yearlyGross($el->map(fn ($x) => ['yearly' => $x->yearly])->all()),
                    ];
                }),
            "Current Salary — {$company->establishment_name}",
        );
    }

    public function index(Request $request, SalaryRevisions $revisions): View
    {
        $revisions->applyDue();

        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();

        $employees = $company
            ? Employee::current()->where('company_id', $company->id)->with(['position', 'salaryElements'])
                ->tap(fn (Builder $q) => $this->search($q, $request, ['employee_code', 'first_name', 'last_name']))
                ->tap(fn (Builder $q) => $this->sort($q, $request, ['code' => 'employee_code', 'name' => 'first_name'], fn (Builder $q) => $q->orderBy('employee_code')))
                ->paginate($perPage = $this->perPage($request, allowAll: true))->withQueryString()
            : null;

        return view('payroll.current', ['companies' => $companies, 'company' => $company, 'employees' => $employees, 'perPage' => $perPage ?? 10, 'revisions' => $revisions]);
    }
}
