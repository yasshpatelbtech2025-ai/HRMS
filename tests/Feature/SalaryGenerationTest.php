<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\SalaryAdjustment;
use App\Models\SalaryAdvance;
use App\Models\SalarySheet;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\SmartAttendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SalaryGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 12:00:00');
        $this->actingAs(User::factory()->create(['name' => 'Srympex']));

        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        PayrollSetting::create(['company_id' => $this->company->id, 'bonus_frequency' => 'monthly']);
        $shift = Shift::factory()->for($this->company)->create(['name' => 'General', 'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']]]);

        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'shift_id' => $shift->id, 'date_of_joining' => '2026-01-01', 'weekly_leave_day' => 'sunday',
            'first_name' => 'Gopal', 'last_name' => 'Bhat', 'employee_code' => '0001', 'pay_frequency' => 'monthly',
            'monthly_salary_calculation' => 'calendar_month', 'daily_hour' => 9,
        ]);
        foreach (['basic' => 12000, 'hra' => 3000] as $element => $amount) {
            $this->employee->salaryElements()->create(['element' => $element, 'monthly' => $amount, 'yearly' => $amount * 12]);
        }
        $this->employee->salaryElements()->create(['element' => 'bonus', 'monthly' => 8.33, 'yearly' => 0]);

        // 21 colleagues without a salary structure take the company past 20 people (PF and Prof Tax apply).
        Employee::factory()->count(21)->create(['company_id' => $this->company->id]);

        // September 2026: present every working day except two (LOP).
        app(SmartAttendance::class)->fill($this->employee->fresh('shift'), 2026, 9);
        Attendance::whereIn('work_date', ['2026-09-10', '2026-09-11'])->orWhereDate('work_date', '2026-09-10')->orWhereDate('work_date', '2026-09-11')->delete();

        SalaryAdvance::create(['company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'amount' => 1000, 'salary_month' => '2026-09-01', 'advance_date' => '2026-09-05']);
        Loan::create([
            'company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'loan_no' => 'LN-0001', 'amount' => 3000, 'interest_rate' => 0,
            'installment_period' => 6, 'repayment_amount' => 3000, 'installment' => 500, 'approved_date' => '2026-08-20', 'repayment_start' => '2026-09-01',
        ]);
        SalaryAdjustment::create(['company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'title' => 'ARREARS', 'amount' => 300, 'salary_month' => '2026-09-01', 'type' => 'earning']);
        SalaryAdjustment::create(['company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'title' => 'Canteen Allowance', 'amount' => 150, 'salary_month' => '2026-09-01', 'type' => 'deduction']);
    }

    private function generate(): TestResponse
    {
        return $this->post(route('payroll.generate'), ['company_id' => $this->company->id, 'salary_month' => '2026-09']);
    }

    public function test_salary_is_worked_out_from_attendance_structure_and_inputs(): void
    {
        $this->generate()->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('error', fn ($m) => str_starts_with($m, 'No salary structure'));

        $payslip = Payslip::where('employee_id', $this->employee->id)->sole();

        // 30 days − 2 LOP = 28 paid days (26 working + 4 Sundays − 2).
        $this->assertEquals(28, $payslip->attendance['paid_days']);
        $this->assertEquals(2, $payslip->attendance['lop']);

        $this->assertEquals(11200, $payslip->amount('earnings', 'basic'), '12,000 × 28 / 30');
        $this->assertEquals(2800, $payslip->amount('earnings', 'hra'));
        $this->assertEquals(932.96, $payslip->amount('earnings', 'bonus'), '8.33% of earned basic, paid monthly');
        $this->assertEquals(300, collect($payslip->earnings)->firstWhere('label', 'ARREARS')['amount']);
        $this->assertEquals(15232.96, $payslip->gross_earnings);

        $this->assertEquals(1344, $payslip->amount('deductions', 'pf'), '12% of 11,200');
        $this->assertEquals(105, $payslip->amount('deductions', 'esi'), '0.75% of 14,000');
        $this->assertEquals(200, $payslip->amount('deductions', 'pt'));
        $this->assertEquals(1000, collect($payslip->deductions)->firstWhere('label', 'SALARY ADVANCE')['amount']);
        $this->assertEquals(500, collect($payslip->deductions)->first(fn ($l) => str_starts_with($l['label'], 'LOAN LN-0001'))['amount']);
        $this->assertEquals(150, collect($payslip->deductions)->firstWhere('label', 'Canteen Allowance')['amount']);
        $this->assertEquals(3299, $payslip->total_deductions);
        $this->assertEquals(11933.96, $payslip->net_pay);

        $this->assertEquals(455, $payslip->amount('employer', 'employer_esi'), '3.25% of 14,000');
        $this->assertEquals(933, $payslip->amount('employer', 'employer_eps'));
    }

    public function test_approval_records_recoveries_and_locks_the_month(): void
    {
        $this->generate();
        $sheet = SalarySheet::sole();

        $this->get(route('payroll.index'))->assertOk()
            ->assertSeeInOrder(['Salary list', 'Sl', 'Company name', 'Salary name', 'Generate date', 'Generate by', 'Status', 'Approved date', 'Approved by', 'Paid date', 'Action'])
            ->assertSeeInOrder(['PI Industry', '2026-09', '05-10-2026', 'Srympex', 'Not approved', 'Salary Approval', 'Employee Salary Chart', 'Delete']);

        $this->put(route('payroll.paid-date', $sheet), ['paid_date' => '2026-10-07'])->assertSessionHas('error');

        $this->get(route('payroll.approval', $sheet))->assertOk()
            ->assertSeeInOrder(['Approve salary', 'Salary Approval', 'PI Industry', 'September 2026'])
            ->assertSeeInOrder(['Gopal Bhat', '28', '2', '11,200.00', '2,800.00', '932.96', '300.00', '15,232.96', '1,344.00', '105.00', '200.00', '1,000.00', '500.00', '150.00', '3,299.00', '11,933.96']);

        $this->put(route('payroll.approve', $sheet))->assertSessionHas('status');
        $this->assertTrue($sheet->fresh()->isApproved());
        $this->assertEquals(1000, SalaryAdvance::sole()->released_amount);
        $this->assertSame(1, Loan::sole()->installments_cleared);

        $this->generate()->assertSessionHasErrors('salary_month');
        $this->delete(route('payroll.destroy', $sheet))->assertSessionHas('error');
        $this->put(route('payroll.payslips.regenerate', Payslip::first()))->assertSessionHasErrors('payslip');

        $this->put(route('payroll.paid-date', $sheet), ['paid_date' => '2026-10-07'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-07', $sheet->fresh()->paid_date->toDateString());

        // Undoing the approval reverses the recoveries.
        $this->put(route('payroll.unapprove', $sheet))->assertSessionHas('status');
        $this->assertEquals(0, SalaryAdvance::sole()->released_amount);
        $this->assertSame(0, Loan::sole()->installments_cleared);
        $this->assertNull($sheet->fresh()->paid_date);
    }

    public function test_regenerating_picks_up_attendance_changes(): void
    {
        $this->generate();
        $payslip = Payslip::where('employee_id', $this->employee->id)->sole();
        $this->assertEquals(28, $payslip->attendance['paid_days']);

        $this->post(route('attendance.monthly.time'), ['employee' => $this->employee->uuid, 'date' => '2026-09-10', 'in_time' => '2026-09-10T09:00', 'out_time' => '2026-09-10T18:00']);

        $this->put(route('payroll.payslips.regenerate', $payslip))->assertRedirect();
        $this->assertEquals(29, Payslip::where('employee_id', $this->employee->id)->sole()->attendance['paid_days']);
    }

    public function test_salary_pages(): void
    {
        $this->generate();
        $payslip = Payslip::where('employee_id', $this->employee->id)->sole();

        $this->get(route('payroll.salaries', ['salary_month' => '2026-09']))->assertOk()
            ->assertSeeInOrder(['Employee salary', 'Sl', 'Company name', 'Employee name', 'Salary month', 'Total salary', 'Action'])
            ->assertSeeInOrder(['PI Industry', 'Gopal Bhat', '2026-09', '11,933.96', 'Payslip', 'Download pay slip', 'Regenerate']);

        $this->get(route('payroll.payslips.show', $payslip))->assertOk()
            ->assertSeeInOrder(['PI Industry', 'Pay slip for the month of September 2026', 'Draft', 'Gopal Bhat', '0001'], false)
            ->assertSeeInOrder(['Earnings', 'Amount', 'Deductions', 'Amount', 'BASIC', '11,200.00', 'PF', '1,344.00'])
            ->assertSee('Net pay: ₹ 11,933.96', false)
            ->assertSee('Rupees Eleven Thousand Nine Hundred Thirty-Three and Ninety-Six Paise Only');

        $this->get(route('payroll.chart.export', SalarySheet::sole()))->assertOk()->assertDownload();
        $this->post(route('payroll.generate'), ['company_id' => $this->company->id, 'salary_month' => '2026-12'])->assertSessionHasErrors('salary_month');
    }

    public function test_thirty_day_and_daily_rate_calculations(): void
    {
        $this->employee->update(['monthly_salary_calculation' => 'thirty_days']);
        $this->generate();
        // 30 − 2 unpaid days, out of 30.
        $this->assertEquals(11200, Payslip::where('employee_id', $this->employee->id)->sole()->amount('earnings', 'basic'));

        $this->employee->update(['pay_frequency' => 'daily', 'daily_rate' => 500]);
        $this->employee->salaryElements()->delete();
        $this->generate();
        $payslip = Payslip::where('employee_id', $this->employee->id)->sole();
        // Daily rate: 24 days worked; Sundays unpaid because the shift doesn't pay daily-rate staff for week-offs.
        $this->assertEquals(24, $payslip->attendance['paid_days']);
        $this->assertEquals(12000, $payslip->amount('earnings', 'basic'));
    }

    public function test_fixed_payable_days_are_entered_at_generate(): void
    {
        $this->employee->update(['monthly_salary_calculation' => 'fixed_payable_days']);

        $this->get(route('payroll.index'))->assertOk()->assertSee('0001 · Gopal Bhat');
        $this->generate()->assertSessionHasErrors('payable_days');
        $this->assertSame(0, SalarySheet::count());

        $this->post(route('payroll.generate'), ['company_id' => $this->company->id, 'salary_month' => '2026-09', 'payable_days' => [$this->employee->id => 26]])
            ->assertSessionHasNoErrors();
        $payslip = Payslip::where('employee_id', $this->employee->id)->sole();
        // 12,000 ÷ 31 × 26, whatever the attendance.
        $this->assertEquals(26, $payslip->attendance['paid_days']);
        $this->assertEquals(10064.52, $payslip->amount('earnings', 'basic'));

        // Regenerating uses the days entered for the month.
        $this->put(route('payroll.payslips.regenerate', $payslip))->assertRedirect();
        $this->assertEquals(10064.52, Payslip::where('employee_id', $this->employee->id)->sole()->amount('earnings', 'basic'));
    }
}
