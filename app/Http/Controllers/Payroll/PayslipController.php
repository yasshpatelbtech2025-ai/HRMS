<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Concerns\ListsRecords;
use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Services\Payroll\SalaryGenerator;
use App\Support\TableExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll › Manage Employee Salary: generated payslips by month, each
 * viewable, printable and (until approved) regenerable.
 */
class PayslipController extends Controller
{
    use ListsRecords;

    public function index(Request $request): View
    {
        $month = $this->month($request);
        $payslips = $this->filtered($request)->paginate($perPage = $this->perPage($request))->withQueryString();

        return view('payroll.salaries', compact('payslips', 'perPage', 'month'));
    }

    public function export(Request $request, string $format): StreamedResponse
    {
        return TableExport::download($format, 'employee-salary'.($this->month($request) ? '-'.$this->month($request) : ''),
            ['Sl', 'Company name', 'Employee name', 'Salary month', 'Total salary'],
            $this->filtered($request)->get()->map(fn (Payslip $p, int $i) => [
                $i + 1, $p->company->label, $p->employee->full_name, $p->month->format('Y-m'), number_format((float) $p->net_pay, 2, '.', ''),
            ]),
            'Employee salary'.($this->month($request) ? ' — '.$this->month($request) : ''),
        );
    }

    private function filtered(Request $request): Builder
    {
        $month = $this->month($request);

        return Payslip::with(['employee', 'company.parent', 'sheet'])
            ->when($month, fn ($q) => $q->whereDate('month', $month.'-01'))
            ->tap(fn (Builder $q) => $this->search($q, $request, ['employee.first_name', 'employee.last_name', 'employee.employee_code']))
            ->orderByDesc('month')->orderBy('company_id')->orderBy('employee_id');
    }

    private function month(Request $request): ?string
    {
        $month = $request->query('salary_month');

        return is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) ? $month : null;
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
