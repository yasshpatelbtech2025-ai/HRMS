<?php

namespace App\Services\Payroll;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\SalaryAdvance;
use App\Models\SalarySheet;
use App\Models\User;
use App\Services\Attendance\MonthlyAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Generates a company's salary sheet for a month (one payslip per employee
 * who worked any of it), and approves it — at which point advance
 * recoveries and loan installments on the payslips are recorded.
 */
class SalaryGenerator
{
    public function __construct(
        private PayrollCalculator $calculator,
        private MonthlyAttendance $attendance,
        private SalaryRevisions $revisions,
    ) {}

    /**
     * @return array{sheet: SalarySheet, skipped: list<string>} skipped: employees without a salary structure
     */
    public function generate(Company $company, CarbonImmutable $month, User $user): array
    {
        $month = $month->startOfMonth();
        $sheet = SalarySheet::where('company_id', $company->id)->whereDate('month', $month)->first();

        if ($sheet?->isApproved()) {
            throw ValidationException::withMessages(['salary_month' => __(':month salary for :company is already approved.', [
                'month' => $month->format('F Y'), 'company' => $company->establishment_name,
            ])]);
        }

        $this->revisions->applyDue();

        return DB::transaction(function () use ($company, $month, $user, $sheet) {
            $sheet ??= new SalarySheet(['company_id' => $company->id, 'month' => $month->toDateString()]);
            $sheet->fill(['generated_by' => $user->id, 'generated_at' => now(), 'status' => 'not_approved'])->save();
            $sheet->payslips()->delete();

            $skipped = [];
            [$employees, $setting, $size] = $this->inputs($company, $month);
            $grid = $this->attendance->forMonth($employees, $month);

            foreach ($employees as $employee) {
                if (! $this->hasSalary($employee)) {
                    $skipped[] = $employee->full_name;

                    continue;
                }

                $this->store($sheet, $employee, $month, $grid[$employee->id], $setting, $size);
            }

            return ['sheet' => $sheet, 'skipped' => $skipped];
        });
    }

    /**
     * Work out one payslip again (e.g. after correcting attendance).
     */
    public function regenerate(Payslip $payslip): Payslip
    {
        $sheet = $payslip->sheet;

        if ($sheet->isApproved()) {
            throw ValidationException::withMessages(['payslip' => __('This salary is approved; it cannot be regenerated.')]);
        }

        $month = CarbonImmutable::parse($sheet->month);
        [$employees, $setting, $size] = $this->inputs($sheet->company, $month, $payslip->employee_id);
        $employee = $employees->firstOrFail();
        $grid = $this->attendance->forMonth($employees, $month);

        return DB::transaction(function () use ($payslip, $sheet, $employee, $month, $grid, $setting, $size) {
            $payslip->delete();

            return $this->store($sheet, $employee, $month, $grid[$employee->id], $setting, $size);
        });
    }

    /**
     * Approve the sheet: advances on it count as recovered and each loan on
     * it moves on by one installment.
     */
    public function approve(SalarySheet $sheet, User $user): void
    {
        DB::transaction(function () use ($sheet, $user) {
            $this->settle($sheet, 1);
            $sheet->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);
        });
    }

    /**
     * Undo an approval, reversing the recoveries it recorded.
     */
    public function unapprove(SalarySheet $sheet): void
    {
        DB::transaction(function () use ($sheet) {
            $this->settle($sheet, -1);
            $sheet->update(['status' => 'not_approved', 'approved_by' => null, 'approved_at' => null, 'paid_date' => null]);
        });
    }

    private function settle(SalarySheet $sheet, int $direction): void
    {
        foreach ($sheet->payslips as $payslip) {
            foreach ($payslip->deductions as $line) {
                [$kind, $uuid] = array_pad(explode(':', $line['key'], 2), 2, null);

                if ($kind === 'advance' && ($advance = SalaryAdvance::where('uuid', $uuid)->first())) {
                    $advance->update(['released_amount' => max(0, (float) $advance->released_amount + $direction * $line['amount'])]);
                }

                if ($kind === 'loan' && ($loan = Loan::where('uuid', $uuid)->first())) {
                    $loan->update(['installments_cleared' => max(0, $loan->installments_cleared + $direction)]);
                }
            }
        }
    }

    /**
     * @return array{Collection<int, Employee>, PayrollSetting, int}
     */
    private function inputs(Company $company, CarbonImmutable $month, ?int $employeeId = null): array
    {
        $employees = Employee::where('company_id', $company->id)
            ->when($employeeId, fn ($q) => $q->whereKey($employeeId))
            ->where(fn ($q) => $q->whereNull('date_of_joining')->orWhereDate('date_of_joining', '<=', $month->endOfMonth()))
            ->where(fn ($q) => $q->whereNull('separation_date')->orWhereDate('separation_date', '>', $month))
            ->with(['shift', 'salaryElements'])
            ->orderBy('employee_code')->get();

        return [
            $employees,
            PayrollSetting::firstOrNew(['company_id' => $company->id]),
            Employee::current()->where('company_id', $company->id)->count(),
        ];
    }

    private function hasSalary(Employee $employee): bool
    {
        return $employee->salaryElements->isNotEmpty() || (float) $employee->daily_rate > 0 || (float) $employee->hourly_rate > 0;
    }

    private function store(SalarySheet $sheet, Employee $employee, CarbonImmutable $month, array $attendance, PayrollSetting $setting, int $size): Payslip
    {
        $result = $this->calculator->calculate($employee, $month, $attendance, $setting, $size);

        return Payslip::create([
            'salary_sheet_id' => $sheet->id, 'company_id' => $employee->company_id, 'employee_id' => $employee->id,
            'month' => $month->toDateString(), 'pay_frequency' => $employee->pay_frequency,
            'attendance' => $result['attendance'], 'earnings' => $result['earnings'], 'deductions' => $result['deductions'],
            'employer' => $result['employer'], 'gross_earnings' => $result['gross'], 'total_deductions' => $result['deduction_total'],
            'net_pay' => $result['net'],
        ]);
    }
}
