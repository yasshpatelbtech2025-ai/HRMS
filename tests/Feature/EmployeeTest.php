<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->company = Company::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'first_name' => 'Asha',
            'last_name' => 'Patel',
            'joining_date' => '2024-04-01',
            'status' => 'active',
        ], $overrides);
    }

    public function test_guests_are_redirected(): void
    {
        auth()->logout();
        $this->get(route('employees.index'))->assertRedirect('/login');
        $this->post(route('employees.store'), $this->payload())->assertRedirect('/login');
    }

    public function test_list_and_form_pages_render(): void
    {
        $employee = Employee::factory()->for($this->company)->create(['first_name' => 'Ravi']);

        $this->get(route('employees.index'))->assertOk()->assertSee('Ravi');
        $this->get(route('employees.separated'))->assertOk()->assertDontSee('Ravi');
        $this->get(route('employees.create'))->assertOk()->assertSee('Aadhaar');
        $this->get(route('employees.edit', $employee))->assertOk()->assertSee('Ravi');
    }

    public function test_employee_is_created_with_full_details_and_auto_code(): void
    {
        $department = Department::factory()->for($this->company)->create();
        $position = Position::factory()->create();

        $this->post(route('employees.store'), $this->payload([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'father_name' => 'Ramesh Patel',
            'gender' => 'female',
            'date_of_birth' => '1995-05-20',
            'mobile' => '9876543210',
            'aadhaar_number' => '123456789012',
            'pan_number' => 'abcde1234f',
            'uan_number' => '100200300400',
            'bank_account_number' => '123456789012',
            'ifsc_code' => 'sbin0001234',
            'gross_salary' => '18500.50',
        ]))->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'first_name' => 'Asha',
            'employee_code' => '0001',
            'pan_number' => 'ABCDE1234F',
            'ifsc_code' => 'SBIN0001234',
            'department_id' => $department->id,
        ]);
    }

    public function test_employee_codes_increment_per_company(): void
    {
        $other = Company::factory()->create();

        $this->post(route('employees.store'), $this->payload());
        $this->post(route('employees.store'), $this->payload(['first_name' => 'Second']));
        $this->post(route('employees.store'), $this->payload(['company_id' => $other->id, 'first_name' => 'Other']));

        $this->assertSame('0002', Employee::where('first_name', 'Second')->value('employee_code'));
        $this->assertSame('0001', Employee::where('first_name', 'Other')->value('employee_code'));
    }

    public function test_validation_rejects_bad_statutory_numbers_and_duplicate_codes(): void
    {
        Employee::factory()->for($this->company)->create(['employee_code' => '0007']);

        $this->post(route('employees.store'), $this->payload([
            'employee_code' => '0007',
            'mobile' => '12345',
            'aadhaar_number' => '1234',
            'pan_number' => 'BADPAN',
            'ifsc_code' => 'BAD',
            'email' => 'nope',
        ]))->assertSessionHasErrors(['employee_code', 'mobile', 'aadhaar_number', 'pan_number', 'ifsc_code', 'email']);
    }

    public function test_department_must_belong_to_the_chosen_company(): void
    {
        $foreign = Department::factory()->create();

        $this->post(route('employees.store'), $this->payload(['department_id' => $foreign->id]))
            ->assertSessionHasErrors('department_id');
    }

    public function test_separating_requires_a_date_and_moves_the_employee_to_the_separated_list(): void
    {
        $employee = Employee::factory()->for($this->company)->create(['first_name' => 'Leaver', 'joining_date' => '2024-01-01']);

        $this->put(route('employees.update', $employee), $this->payload(['first_name' => 'Leaver', 'status' => 'separated']))
            ->assertSessionHasErrors('separation_date');

        $this->put(route('employees.update', $employee), $this->payload([
            'first_name' => 'Leaver', 'status' => 'separated', 'separation_date' => '2025-01-31',
        ]))->assertRedirect(route('employees.separated'));

        $this->get(route('employees.separated'))->assertSee('Leaver');
        $this->get(route('employees.index'))->assertDontSee('Leaver');
    }

    public function test_update_keeps_existing_code_when_left_blank(): void
    {
        $employee = Employee::factory()->for($this->company)->create(['employee_code' => '0042']);

        $this->put(route('employees.update', $employee), $this->payload(['employee_code' => '']))->assertRedirect();

        $this->assertSame('0042', $employee->fresh()->employee_code);
    }

    public function test_filters_and_search(): void
    {
        $other = Company::factory()->create();
        Employee::factory()->for($this->company)->create(['first_name' => 'Alpha']);
        Employee::factory()->for($other)->create(['first_name' => 'Beta']);

        $this->get(route('employees.index', ['company_id' => $other->id]))->assertSee('Beta')->assertDontSee('Alpha');
        $this->get(route('employees.index', ['search' => 'Alph']))->assertSee('Alpha')->assertDontSee('Beta');
    }

    public function test_employee_can_be_deleted(): void
    {
        $employee = Employee::factory()->for($this->company)->create();

        $this->delete(route('employees.destroy', $employee))->assertRedirect();
        $this->assertSoftDeleted($employee);
    }

    public function test_dashboard_counts_active_employees_only(): void
    {
        Employee::factory()->for($this->company)->count(3)->create();
        Employee::factory()->for($this->company)->separated()->create();

        $this->get('/dashboard')->assertViewHas('stats', fn ($stats) => $stats['employees'] === 3);
    }
}
