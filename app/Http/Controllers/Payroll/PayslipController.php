<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Services\Payroll\SalaryGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Payroll › Manage Employee Salary: generated payslips by month, each
 * viewable, printable and (until approved) regenerable.
 */
class PayslipController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        $month = is_string($request->query('salary_month')) && preg_match('/^\d{4}-\d{2}$/', $request->query('salary_month')) ? $request->query('salary_month') : null;

        $payslips = Payslip::with(['employee', 'company.parent', 'sheet'])
            ->when($month, fn ($q) => $q->whereDate('month', $month.'-01'))
            ->tap(fn (Builder $q) => $this->search($q, $request, ['employee.first_name', 'employee.last_name', 'employee.employee_code']))
            ->orderByDesc('month')->orderBy('company_id')->orderBy('employee_id')
            ->paginate($perPage = $this->perPage($request))->withQueryString();

        return view('payroll.salaries', compact('payslips', 'perPage', 'month'));
    }

    public function show(Request $request, Payslip $payslip): View
    {
        $payslip->load(['employee.position', 'employee.subDepartment', 'employee.department', 'company', 'sheet']);

        return view('payroll.payslip', ['payslip' => $payslip, 'print' => $request->boolean('print')]);
    }

    public function regenerate(Payslip $payslip, SalaryGenerator $generator): RedirectResponse
    {
        $fresh = $generator->regenerate($payslip);

        return redirect()->route('payroll.payslips.show', $fresh)->with('status', __('Payslip regenerated.'));
    }
}
