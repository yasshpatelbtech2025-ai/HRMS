<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Services\Payroll\SalaryRevisions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Payroll › Current Salary: each current employee's salary type, rates and
 * main salary elements for a company.
 */
class CurrentSalaryController extends Controller
{
    use ListsRecords;

    public function index(Request $request, SalaryRevisions $revisions): View
    {
        $revisions->applyDue();

        $companies = Company::with('parent')->orderBy('establishment_name')->get();
        $company = $companies->firstWhere('uuid', $request->query('company')) ?? $companies->first();

        $employees = $company
            ? Employee::current()->where('company_id', $company->id)->with(['position', 'salaryElements'])
                ->tap(fn (Builder $q) => $this->search($q, $request, ['employee_code', 'first_name', 'last_name']))
                ->orderBy('employee_code')->paginate($perPage = $this->perPage($request, allowAll: true))->withQueryString()
            : null;

        return view('payroll.current', ['companies' => $companies, 'company' => $company, 'employees' => $employees, 'perPage' => $perPage ?? 10, 'revisions' => $revisions]);
    }
}
