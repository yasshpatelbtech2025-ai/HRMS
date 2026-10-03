<?php

namespace Tests\Feature;

use App\Models\BonusSheet;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollSetting;
use App\Models\Payslip;
use App\Models\SalarySheet;
use App\Models\Shift;
use App\Models\TdsOverride;
use App\Models\User;
use App\Services\Attendance\SmartAttendance;
use App\Services\Payroll\IncomeTax;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TdsAndBonusTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 12:00:00');
        $this->actingAs(User::factory()->create(['name' => 'Asha Patel']));
        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
    }

    private function employee(array $salary, array $attributes = []): Employee
    {
        $employee = Employee::factory()->create($attributes + ['company_id' => $this->company->id, 'pay_frequency' => 'monthly', 'date_of_joining' => '2024-01-01', 'tax_regime' => 'new']);
        foreach ($salary as $element => $amount) {
            $employee->salaryElements()->create(['element' => $element, 'monthly' => $amount, 'yearly' => $amount * 12]);
        }

        return $employee->load('salaryElements');
    }

    /** A payslip for a month with basic (+ DA) earnings. */
    private function payslip(Employee $employee, string $month, float $basic, float $da = 0, array $deductions = []): Payslip
    {
        $sheet = SalarySheet::where('company_id', $this->company->id)->whereDate('month', $month)->first()
            ?? SalarySheet::create(['company_id' => $this->company->id, 'month' => $month, 'generated_at' => now()]);

        return Payslip::create([
            'salary_sheet_id' => $sheet->id, 'company_id' => $this->company->id, 'employee_id' => $employee->id, 'month' => $month, 'pay_frequency' => 'monthly',
            'attendance' => ['present' => 26, 'half_days' => 0], 'employer' => [],
            'earnings' => [['key' => 'basic', 'label' => 'BASIC', 'amount' => $basic], ['key' => 'da', 'label' => 'DA', 'amount' => $da]],
            'deductions' => $deductions, 'gross_earnings' => $basic + $da, 'total_deductions' => 0, 'net_pay' => $basic + $da,
        ]);
    }

    public function test_tax_follows_the_regime_slabs_rebate_and_cess(): void
    {
        $tax = app(IncomeTax::class);

        $this->assertSame(0.0, $tax->tax(1200000, 'new'));                // 87A: nothing up to ₹12 lakh
        $this->assertSame(52000.0, $tax->tax(1250000, 'new'));            // marginal relief: 50,000 + cess
        $this->assertSame(208000.0, $tax->tax(2000000, 'new'));           // 2,00,000 + 4%
        $this->assertSame(0.0, $tax->tax(500000, 'old'));
        $this->assertSame(33800.0, $tax->tax(600000, 'old'));             // 12,500 + 20,000 + 4%
        $this->assertSame(2026, IncomeTax::fy(CarbonImmutable::parse('2026-04-01')));
        $this->assertSame(2025, IncomeTax::fy(CarbonImmutable::parse('2026-03-31')));
    }

    public function test_payroll_deducts_tds_and_the_tds_page_shows_and_overrides_it(): void
    {
        PayrollSetting::create(['company_id' => $this->company->id, 'tax_deduction' => true]);
        $shift = Shift::factory()->for($this->company)->create(['timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']]]);
        $employee = $this->employee(['basic' => 150000, 'hra' => 50000], ['shift_id' => $shift->id, 'weekly_leave_day' => 'sunday', 'monthly_salary_calculation' => 'calendar_month', 'pan_card_no' => 'ABCDE1234F']);
        app(SmartAttendance::class)->fill($employee->fresh('shift'), 2026, 9);

        $this->post(route('payroll.generate'), ['company_id' => $this->company->id, 'salary_month' => '2026-09'])->assertSessionHasNoErrors();
        $payslip = Payslip::where('employee_id', $employee->id)->sole();

        // September to March: 7 months at ₹2,00,000 = ₹14,00,000; less ₹75,000 = ₹13,25,000 taxable;
        // tax 20,000 + 40,000 + 18,750 = 78,750 + 4% = 81,900, over 7 months = 11,700.
        $this->assertEquals(200000, $payslip->gross_earnings);
        $this->assertEquals(11700, $payslip->amount('deductions', 'tds'));

        $this->get(route('payroll.tds', ['company' => $this->company->uuid, 'fy' => 2026]))->assertOk()
            ->assertSeeInOrder(['TDS (Income Tax)', 'April 2026', $employee->full_name, 'ABCDE1234F', 'New', '1,400,000.00', 'paid so far 200,000.00', '1,325,000.00', 'after 75,000.00 deductions', '81,900.00', '11,700.00', '70,200.00', 'Estimated']);

        $this->post(route('payroll.tds.override', $employee), ['fy' => 2026, 'monthly_amount' => 9000])->assertSessionHasNoErrors();
        $this->assertSame(9000.0, app(IncomeTax::class)->project($employee, CarbonImmutable::parse('2026-10-01'))['monthly_tds']);
        $this->get(route('payroll.tds', ['company' => $this->company->uuid, 'fy' => 2026]))->assertSee('Set by hand');
        $this->post(route('payroll.tds.override', $employee), ['fy' => 2026, 'monthly_amount' => ''])->assertSessionHasNoErrors();
        $this->assertSame(0, TdsOverride::count());

        $this->get(route('payroll.tds.export', ['format' => 'csv', 'company' => $this->company->uuid, 'fy' => 2026]))->assertOk();

        // Without "Employee Tax Deduction" the page says so and payroll leaves TDS out.
        PayrollSetting::where('company_id', $this->company->id)->update(['tax_deduction' => false]);
        $this->get(route('payroll.tds', ['company' => $this->company->uuid]))->assertSee('does not deduct TDS from salary');
    }

    public function test_yearly_bonus_sheet_is_worked_out_approved_and_feeds_the_bonus_forms(): void
    {
        PayrollSetting::create(['company_id' => $this->company->id, 'bonus_frequency' => 'yearly']);
        $worker = $this->employee(['basic' => 10000, 'bonus' => 8.33], ['first_name' => 'Gopal', 'last_name' => 'Bhat']);
        $manager = $this->employee(['basic' => 30000], ['first_name' => 'Ravi', 'last_name' => 'Shah']);
        for ($m = CarbonImmutable::parse('2025-04-01'); $m->lte(CarbonImmutable::parse('2026-03-01')); $m = $m->addMonth()) {
            $this->payslip($worker, $m->toDateString(), 10000);
            $this->payslip($manager, $m->toDateString(), 30000);
        }

        $this->get(route('payroll.bonus', ['company' => $this->company->uuid]))->assertOk()->assertSee('2025 - 2026')->assertSee('Work out bonus');
        $this->post(route('payroll.bonus.generate'), ['company' => $this->company->uuid, 'fy' => 2025])->assertSessionHas('status');

        $sheet = BonusSheet::sole();
        $entries = $sheet->entries->keyBy('employee_id');
        // Worker: basic capped at ₹7,000 a month × 12 = 84,000 × 8.33% = 6,997. Manager: over ₹21,000 a month.
        $this->assertEquals([true, 120000, 84000, 6997], [$entries[$worker->id]->eligible, $entries[$worker->id]->salary, $entries[$worker->id]->bonus_salary, $entries[$worker->id]->amount]);
        $this->assertEquals([false, 0], [$entries[$manager->id]->eligible, $entries[$manager->id]->amount]);

        $this->get(route('payroll.bonus', ['company' => $this->company->uuid, 'fy' => 2025]))->assertOk()
            ->assertSeeInOrder(['Draft', 'Gopal Bhat', '312', '120,000.00', '84,000.00', '8.33%', 'Yes', '6,997.00', 'Ravi Shah', 'No', 'Salary over 21,000 a month', 'Total']);

        $this->put(route('payroll.bonus.update', $sheet), ['amounts' => [$entries[$worker->id]->id => 7000]])->assertSessionHasNoErrors();
        $this->post(route('payroll.bonus.approve', $sheet), ['paid_on' => '2026-04-10'])->assertSessionHasNoErrors();
        $this->assertTrue($sheet->fresh()->isApproved());
        $this->post(route('payroll.bonus.generate'), ['company' => $this->company->uuid, 'fy' => 2025])->assertStatus(422);

        // Bonus Form C and D use the approved sheet; TDS counts it as income.
        $this->get(route('compliance.forms.show', [$this->company, 'bonus-form-c', 'year' => 2025, 'output' => 'print']))->assertOk()
            ->assertSeeInOrder(['Gopal Bhat', '312', '120,000.00', '7,000.00', '7,000.00', '10-04-2026']);
        $this->get(route('compliance.forms.show', [$this->company, 'payment-bonus-form-d', 'year' => 2025]))->assertSee('7,000.00 paid as bonus')->assertSee('10-04-2026');
        $this->assertEquals(7000, app(IncomeTax::class)->project($worker, CarbonImmutable::parse('2026-03-01'))['bonus']);

        $this->get(route('payroll.bonus.export', [$sheet, 'csv']))->assertOk();
        $this->post(route('payroll.bonus.unapprove', $sheet))->assertSessionHas('status');
        $this->delete(route('payroll.bonus.destroy', $sheet))->assertSessionHas('status');
        $this->assertSame(0, BonusSheet::count());
    }
}
