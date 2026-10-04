<?php

namespace App\Http\Controllers\Payroll;

use App\Rules\AccessibleCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payslip;
use App\Models\SalarySheet;
use App\Services\Payroll\SalaryGenerator;
use App\Support\TableExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payroll › Salary Generate: generate a company's salary for a month, then
 * review it on the salary chart, approve it and record the paid date.
 */
class SalaryGenerateController extends Controller
{
    public function __construct(private SalaryGenerator $generator) {}

    public function index(): View
    {
        return view('payroll.generate', [
            'companies' => Company::with('parent')->orderBy('establishment_name')->get(),
            'sheets' => SalarySheet::with(['company.parent', 'generator', 'approver'])->withCount('payslips')
                ->orderByDesc('month')->orderByDesc('generated_at')->paginate(25),
        ]);
    }

    public function exportList(string $format): StreamedResponse
    {
        return TableExport::download($format, 'salary-list',
            ['Sl', 'Company name', 'Salary name', 'Generate date', 'Generate by', 'Status', 'Approved date', 'Approved by', 'Paid date'],
            SalarySheet::with(['company.parent', 'generator', 'approver'])->orderByDesc('month')->orderByDesc('generated_at')->get()
                ->map(fn (SalarySheet $s, int $i) => [
                    $i + 1, $s->company->label, $s->month->format('Y-m'), $s->generated_at->format('d-m-Y'), $s->generator?->name,
                    $s->isApproved() ? 'Approved' : 'Not approved', $s->approved_at?->format('d-m-Y'), $s->approver?->name, $s->paid_date?->format('d-m-Y'),
                ]),
            'Salary list',
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', new AccessibleCompany('id'), Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'salary_month' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
        ], ['salary_month.before_or_equal' => __('Salary can only be generated for this month or earlier.')], ['company_id' => 'company', 'salary_month' => 'salary month']);

        $company = Company::findOrFail($data['company_id']);
        $result = $this->generator->generate($company, CarbonImmutable::createFromFormat('!Y-m', $data['salary_month']), $request->user());

        $redirect = redirect()->route('payroll.approval', $result['sheet'])->with('status', __(':month salary generated for :count employee(s) of :company.', [
            'month' => $data['salary_month'], 'count' => $result['sheet']->payslips()->count(), 'company' => $company->establishment_name,
        ]));

        return $result['skipped']
            ? $redirect->with('error', __('No salary structure, so not included: :names', ['names' => implode(', ', $result['skipped'])]))
            : $redirect;
    }

    /**
     * "Salary Approval": the salary chart with approve / undo approval.
     */
    public function approval(SalarySheet $sheet): View
    {
        return view('payroll.chart', ['sheet' => $sheet, 'chart' => $this->chart($sheet), 'approval' => true]);
    }

    /**
     * "Employee Salary Chart": every employee's figures for the month.
     */
    public function chartPage(SalarySheet $sheet): View
    {
        return view('payroll.chart', ['sheet' => $sheet, 'chart' => $this->chart($sheet), 'approval' => false]);
    }

    public function approve(Request $request, SalarySheet $sheet): RedirectResponse
    {
        abort_if($sheet->isApproved(), 404);
        $this->generator->approve($sheet, $request->user());

        return back()->with('status', __('Salary approved.'));
    }

    public function unapprove(SalarySheet $sheet): RedirectResponse
    {
        abort_unless($sheet->isApproved(), 404);
        $this->generator->unapprove($sheet);

        return back()->with('status', __('Approval undone; advance recoveries and loan installments reversed.'));
    }

    public function paidDate(Request $request, SalarySheet $sheet): RedirectResponse
    {
        if (! $sheet->isApproved()) {
            return back()->with('error', __('Approve the salary before adding its paid date.'));
        }

        $data = $request->validate(['paid_date' => ['required', 'date', 'after_or_equal:'.$sheet->month->toDateString()]], [], ['paid_date' => 'paid date']);
        $sheet->update($data);

        return back()->with('status', __('Paid date saved.'));
    }

    public function destroy(SalarySheet $sheet): RedirectResponse
    {
        if ($sheet->isApproved()) {
            return back()->with('error', __('An approved salary cannot be deleted. Undo the approval first.'));
        }

        $sheet->delete();

        return redirect()->route('payroll.index')->with('status', __('Salary sheet deleted.'));
    }

    public function export(Request $request, SalarySheet $sheet): StreamedResponse
    {
        $chart = $this->chart($sheet);
        $headings = ['Sl', 'Employee id', 'Employee name', 'Paid days', 'LOP', 'OT hrs', ...$chart['earnings'], 'Gross', ...$chart['deductions'], 'Total deductions', 'Net pay', ...$chart['employer']];

        return TableExport::download($request->query('format') === 'csv' ? 'csv' : 'xlsx', 'salary-'.$sheet->month->format('Y-m').'-'.Str::slug($sheet->company->establishment_name), $headings,
            $chart['rows']->values()->map(fn ($row, $i) => [
                $i + 1, $row['code'], $row['name'], $row['paid_days'], $row['lop'], $row['ot'],
                ...array_map(fn ($l) => $row['earnings'][$l] ?? 0, $chart['earnings']), $row['gross'],
                ...array_map(fn ($l) => $row['deductions'][$l] ?? 0, $chart['deductions']), $row['total_deductions'], $row['net'],
                ...array_map(fn ($l) => $row['employer'][$l] ?? 0, $chart['employer']),
            ]),
        );
    }

    /**
     * The sheet as columns (every earning, deduction and employer line that
     * appears on any payslip) and one row per employee.
     *
     * @return array{earnings: list<string>, deductions: list<string>, employer: list<string>, rows: Collection<int, array>, totals: array}
     */
    private function chart(SalarySheet $sheet): array
    {
        $sheet->load(['company', 'payslips.employee.position']);
        $payslips = $sheet->payslips->sortBy(fn (Payslip $p) => $p->employee->employee_code);
        // Loan and advance lines carry their own numbers; group them into one column each.
        $label = fn (array $line) => match (explode(':', $line['key'])[0]) {
            'loan' => 'LOAN', 'advance' => 'SALARY ADVANCE', default => $line['label'],
        };
        $columns = fn (string $list) => $payslips->flatMap(fn ($p) => array_map($label, $p->{$list}))->unique()->values()->all();
        $sumBy = fn (array $lines) => collect($lines)->groupBy($label)->map(fn ($g) => round($g->sum('amount'), 2))->all();

        $rows = $payslips->map(fn (Payslip $p) => [
            'uuid' => $p->uuid,
            'code' => $p->employee->employee_code,
            'name' => $p->employee->full_name,
            'designation' => $p->employee->position?->name,
            'paid_days' => $p->attendance['paid_days'],
            'lop' => $p->attendance['lop'] + $p->attendance['unpaid_leave'],
            'ot' => $p->attendance['ot_hours'],
            'earnings' => $sumBy($p->earnings),
            'deductions' => $sumBy($p->deductions),
            'employer' => $sumBy($p->employer),
            'gross' => (float) $p->gross_earnings,
            'total_deductions' => (float) $p->total_deductions,
            'net' => (float) $p->net_pay,
        ]);

        return [
            'earnings' => $columns('earnings'),
            'deductions' => $columns('deductions'),
            'employer' => $columns('employer'),
            'rows' => $rows,
            'totals' => [
                'gross' => round($rows->sum('gross'), 2),
                'total_deductions' => round($rows->sum('total_deductions'), 2),
                'net' => round($rows->sum('net'), 2),
            ],
        ];
    }
}
