<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\SalarySheet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_payroll_tab_offers_csv_and_excel_and_manage_salary_also_copy_pdf_print(): void
    {
        Carbon::setTestNow('2026-10-05 12:00:00');
        $this->actingAs(User::factory()->create());
        $company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        $employee = Employee::factory()->create(['company_id' => $company->id, 'first_name' => 'Gopal', 'last_name' => 'Bhat']);
        $sheet = SalarySheet::create(['company_id' => $company->id, 'month' => '2026-09-01', 'generated_at' => now()]);
        Payslip::create([
            'salary_sheet_id' => $sheet->id, 'company_id' => $company->id, 'employee_id' => $employee->id, 'month' => '2026-09-01',
            'pay_frequency' => 'monthly', 'attendance' => ['paid_days' => 30, 'lop' => 0, 'unpaid_leave' => 0, 'ot_hours' => 0],
            'earnings' => [['key' => 'basic', 'label' => 'BASIC', 'amount' => 12000]], 'deductions' => [], 'employer' => [],
            'gross_earnings' => 12000, 'total_deductions' => 0, 'net_pay' => 12000,
        ]);

        $pages = [
            'payroll.index' => [],
            'payroll.salaries' => [],
            'payroll.advances' => [],
            'payroll.adjustments' => [],
            'payroll.revisions' => [],
            'payroll.current' => ['company' => $company->uuid],
            'payroll.loans' => [],
        ];
        foreach ($pages as $route => $query) {
            $this->get(route($route, $query))->assertOk()->assertSeeInOrder(['CSV', 'Excel'], false);
        }
        $this->get(route('payroll.salaries'))->assertSeeInOrder(['Copy', 'CSV', 'Excel', 'PDF', 'Print']);
        $this->get(route('payroll.chart', $sheet))->assertSeeInOrder(['CSV', 'Excel', 'Print']);

        $exports = [
            'payroll.export' => ['format' => 'xlsx'],
            'payroll.advances.export' => ['format' => 'xlsx'],
            'payroll.adjustments.export' => ['format' => 'xlsx'],
            'payroll.revisions.export' => ['format' => 'xlsx'],
            'payroll.current.export' => ['format' => 'xlsx', 'company' => $company->uuid],
            'payroll.loans.export' => ['format' => 'xlsx'],
            'payroll.salaries.export' => ['format' => 'xlsx'],
        ];
        foreach ($exports as $route => $params) {
            $this->get(route($route, $params))->assertOk()->assertDownload();
            $this->get(route($route, ['format' => 'csv'] + $params))->assertOk()->assertDownload();
        }

        $csv = $this->get(route('payroll.salaries.export', ['format' => 'csv', 'salary_month' => '2026-09']))->streamedContent();
        $this->assertStringContainsString('Gopal Bhat', $csv);
        $this->assertStringContainsString('12000.00', $csv);

        $pdf = $this->get(route('payroll.salaries.export', ['format' => 'pdf']))->assertOk()->assertDownload('employee-salary.pdf')->streamedContent();
        $this->assertStringStartsWith('%PDF', $pdf);

        $this->get(route('payroll.chart.export', [$sheet, 'format' => 'csv']))->assertOk()->assertDownload();
        $this->get(route('payroll.salaries.export', ['format' => 'docx']))->assertNotFound();
    }
}
