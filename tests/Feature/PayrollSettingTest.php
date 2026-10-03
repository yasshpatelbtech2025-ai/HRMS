<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use App\Models\Position;
use App\Models\Shift;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollSettingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
    }

    private function settings(array $overrides = []): array
    {
        return $overrides + [
            'probation_period_months' => 6, 'notice_period_days' => 30, 'salary_structure' => 'new', 'bonus_frequency' => 'monthly',
            'tax_deduction' => 0, 'lwf_paid_by_company' => 1, 'pf_effect_on' => 'basic', 'esi_effect_on' => 'gross',
        ];
    }

    public function test_page_shows_every_setting(): void
    {
        $this->get(route('configs.payroll'))
            ->assertOk()
            ->assertSeeInOrder(['Attendance', 'Payroll', 'Payroll setting', 'Create setting', 'PI Industry',
                'Employee Prohibition Period ? (In Months)', 'Employee Notice Period ? (In Days)', 'Employee Salary Structure ?',
                'Employee Bouns Paid Frequency ?', 'Employee Tax Deducation ?', 'LWF Contribution by Company ?',
                'Employee PF Deduction Effect On ?', 'Employee ESI Deduction Effect On ?']);
    }

    public function test_settings_are_saved_per_company(): void
    {
        $this->put(route('configs.payroll.update', $this->company), $this->settings())
            ->assertSessionHasNoErrors()->assertRedirect(route('configs.payroll', ['company' => $this->company->uuid]));

        $setting = PayrollSetting::sole();
        $this->assertSame([6, 30, 'new', 'monthly', false, true], [
            $setting->probation_period_months, $setting->notice_period_days, $setting->salary_structure,
            $setting->bonus_frequency, $setting->tax_deduction, $setting->lwf_paid_by_company,
        ]);

        $this->put(route('configs.payroll.update', $this->company), $this->settings(['salary_structure' => 'newest', 'notice_period_days' => -1]))
            ->assertSessionHasErrors(['salary_structure', 'notice_period_days']);
    }

    public function test_variable_bonus_rules(): void
    {
        $rule = ['company_id' => $this->company->id, 'type' => 'variable_bonus', 'name' => 'Diwali bonus', 'amount' => 8.33, 'month' => '2026-10', 'is_percent' => 1, 'effect_on' => 'basic', 'is_active' => 1];

        $this->post(route('configs.payroll.rules.store'), $rule)->assertSessionHasNoErrors();
        $saved = PayrollRule::sole();
        $this->assertSame('2026-10-01', $saved->month->toDateString());
        $this->assertTrue($saved->is_percent);

        $this->get(route('configs.payroll'))->assertSeeInOrder(['Payroll rules', 'Variable Bonus', 'Diwali bonus', 'October 2026', '8.33% of basic', 'Active']);

        $this->post(route('configs.payroll.rules.store'), ['amount' => 120] + $rule)->assertSessionHasErrors('amount');

        $this->put(route('configs.payroll.rules.update', $saved), ['amount' => 5000, 'is_percent' => 0, 'is_active' => 0] + $rule)->assertSessionHasNoErrors();
        $this->assertEquals(5000, $saved->fresh()->amount);
        $this->assertFalse($saved->fresh()->is_active);

        $this->delete(route('configs.payroll.rules.destroy', $saved))->assertSessionHas('status');
        $this->assertModelMissing($saved);
    }

    public function test_new_structure_limits_the_employee_salary_elements(): void
    {
        PayrollSetting::create(['company_id' => $this->company->id, 'salary_structure' => 'new']);
        $department = Department::factory()->for($this->company)->create();

        $payload = [
            'first_name' => 'Ravi', 'last_name' => 'Thakor', 'gender' => 'male', 'email' => 'ravi@example.com', 'phone' => '9000000001',
            'date_of_birth' => '1999-01-01', 'country' => 'India', 'company_id' => $this->company->id,
            'shift_id' => Shift::factory()->for($this->company)->create()->id, 'date_of_joining' => '2026-01-01',
            'works_on_hazardous_process' => 0, 'can_punch_in_hybrid_mode' => 0, 'department_id' => $department->id,
            'sub_department_id' => SubDepartment::factory()->for($department)->create(['company_id' => $this->company->id])->id,
            'position_id' => Position::factory()->for($this->company)->create()->id, 'working_place' => 'automated_equipment_only',
            'employment_type' => 'full_time', 'pay_frequency' => 'monthly', 'daily_hour' => 8, 'tax_regime' => 'new',
            'monthly_salary_calculation' => 'calendar_month', 'weekly_leave_day' => 'sunday',
        ];

        $this->post(route('employees.store'), $payload + ['salary' => ['basic' => ['monthly' => 8000], 'allowance' => ['monthly' => 9000], 'hra' => ['monthly' => 1000]]])
            ->assertSessionHasErrors(['salary.basic.monthly', 'salary.hra.monthly']);

        $this->post(route('employees.store'), $payload + ['salary' => ['basic' => ['monthly' => 9000], 'allowance' => ['monthly' => 8000]]])
            ->assertSessionHasNoErrors();
    }
}
