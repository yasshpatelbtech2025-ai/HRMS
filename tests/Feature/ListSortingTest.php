<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_list_sorts_by_each_of_its_columns(): void
    {
        $this->actingAs(User::factory()->create());
        $company = Company::factory()->create();
        Employee::factory()->count(2)->create(['company_id' => $company->id]);

        $lists = [
            'employees.separations' => ['code', 'name', 'joined', 'separated'],
            'employees.id-cards' => ['code', 'name', 'assigned'],
            'attendance.leave.holiday-list' => ['name', 'from', 'to'],
            'attendance.leave' => ['employee', 'applied', 'start', 'end', 'days', 'status'],
            'attendance.leave.types' => ['name', 'days'],
            'attendance.requests' => ['name', 'date', 'status'],
            'payroll.advances' => ['employee', 'amount', 'month', 'date'],
            'payroll.current' => ['code', 'name'],
            'payroll.adjustments' => ['employee', 'title', 'amount', 'month'],
            'payroll.salaries' => ['employee', 'month', 'total'],
            'payroll.loans' => ['employee', 'loan', 'amount', 'approved', 'status'],
        ];

        foreach ($lists as $route => $keys) {
            foreach ($keys as $key) {
                foreach (['asc', 'desc'] as $direction) {
                    $this->get(route($route, ['sort' => $key, 'direction' => $direction]))->assertOk();
                }
            }
            // The headings link to the sort.
            $this->get(route($route))->assertSee('sort='.$keys[0], false);
        }
    }

    public function test_sorting_orders_rows_and_ignores_unknown_columns(): void
    {
        $this->actingAs(User::factory()->create());
        $company = Company::factory()->create();
        foreach ([['Zara', 500], ['Amit', 3000], ['Meera', 1200]] as [$name, $amount]) {
            $employee = Employee::factory()->create(['company_id' => $company->id, 'first_name' => $name, 'last_name' => 'Test']);
            SalaryAdvance::create(['company_id' => $company->id, 'employee_id' => $employee->id, 'amount' => $amount, 'salary_month' => '2026-09-01', 'advance_date' => '2026-09-05']);
        }

        $this->get(route('payroll.advances', ['sort' => 'amount', 'direction' => 'desc']))->assertSeeInOrder(['Amit Test', 'Meera Test', 'Zara Test']);
        $this->get(route('payroll.advances', ['sort' => 'employee']))->assertSeeInOrder(['Amit Test', 'Meera Test', 'Zara Test']);
        $this->get(route('payroll.advances', ['sort' => 'employee', 'direction' => 'desc']))->assertSeeInOrder(['Zara Test', 'Meera Test', 'Amit Test']);
        $this->get(route('payroll.advances', ['sort' => 'password', 'direction' => 'desc; drop table users']))->assertOk();
    }
}
