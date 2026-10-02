<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->company = Company::factory()->create(['establishment_name' => 'Acme Textiles']);
        $this->department = Department::factory()->for($this->company)->create(['name' => 'Finance']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'employee_code' => 'EMP0100',
            'first_name' => 'Asha',
            'last_name' => 'Mehta',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'gender' => 'female',
            'date_of_birth' => '1994-05-12',
            'date_of_joining' => '2026-09-01',
            'designation' => 'Accountant',
            'employment_type' => 'full_time',
            'status' => 'active',
            'address' => 'Mumbai',
        ], $overrides);
    }

    public function test_employee_pages_require_login(): void
    {
        auth()->logout();

        $this->get(route('employees.index'))->assertRedirect('/login');
        $this->get(route('employees.create'))->assertRedirect('/login');
        $this->post(route('employees.store'), $this->payload())->assertRedirect('/login');
    }

    public function test_index_lists_employees_and_shows_empty_state(): void
    {
        $this->get(route('employees.index'))->assertOk()->assertSeeText('No employees yet');

        $employee = Employee::factory()->create();

        $this->get(route('employees.index'))->assertOk()->assertSeeText($employee->full_name);
    }

    public function test_index_filters_by_search_status_company_and_department(): void
    {
        $other = Company::factory()->create();
        Employee::factory()->for($this->company)->for($this->department)->create(['first_name' => 'Ravi', 'status' => 'active']);
        Employee::factory()->for($other)->create(['first_name' => 'Neha', 'status' => 'inactive']);

        $this->get(route('employees.index', ['search' => 'rav']))->assertSeeText('Ravi')->assertDontSeeText('Neha');
        $this->get(route('employees.index', ['status' => 'inactive']))->assertSeeText('Neha')->assertDontSeeText('Ravi');
        $this->get(route('employees.index', ['company_id' => $other->id]))->assertSeeText('Neha')->assertDontSeeText('Ravi');
        $this->get(route('employees.index', ['department_id' => $this->department->id]))->assertSeeText('Ravi')->assertDontSeeText('Neha');
    }

    public function test_create_form_suggests_next_code(): void
    {
        $this->get(route('employees.create'))->assertOk()->assertSee('value="EMP0001"', false);
    }

    public function test_employee_can_be_created(): void
    {
        $response = $this->post(route('employees.store'), $this->payload(['email' => 'Asha@Example.com', 'employee_code' => 'emp0100']));

        $employee = Employee::sole();
        $response->assertRedirect(route('employees.show', $employee));
        $this->assertSame('asha@example.com', $employee->email);
        $this->assertSame('EMP0100', $employee->employee_code);
        $this->assertSame('2026-09-01', $employee->date_of_joining->toDateString());
        $this->assertTrue($employee->company->is($this->company));
        $this->assertTrue($employee->department->is($this->department));
    }

    public function test_company_is_required_and_department_must_belong_to_it(): void
    {
        $foreign = Department::factory()->create();

        $this->post(route('employees.store'), $this->payload(['company_id' => '']))->assertSessionHasErrors('company_id');
        $this->post(route('employees.store'), $this->payload(['department_id' => $foreign->id]))->assertSessionHasErrors('department_id');
        $this->post(route('employees.store'), $this->payload(['department_id' => '']))->assertSessionHasNoErrors();

        $this->assertNull(Employee::sole()->department_id);
    }

    public function test_archived_companies_cannot_be_chosen(): void
    {
        $this->company->delete();

        $this->post(route('employees.store'), $this->payload(['department_id' => '']))->assertSessionHasErrors('company_id');
    }

    public function test_form_offers_companies_and_their_departments(): void
    {
        $this->get(route('employees.create'))->assertOk()->assertSeeText('Acme Textiles')->assertSee('Finance');
    }

    public function test_store_validates_required_and_unique_fields(): void
    {
        $existing = Employee::factory()->create();

        $this->post(route('employees.store'), $this->payload([
            'first_name' => '',
            'email' => $existing->email,
            'employee_code' => $existing->employee_code,
            'status' => 'retired',
        ]))->assertSessionHasErrors(['first_name', 'email', 'employee_code', 'status']);

        $this->assertSame(1, Employee::count());
    }

    public function test_show_and_edit_render(): void
    {
        $employee = Employee::factory()->create();

        $this->get(route('employees.show', $employee))->assertOk()->assertSeeText($employee->employee_code)->assertSeeText($employee->company->establishment_name);
        $this->get(route('employees.edit', $employee))->assertOk()->assertSee('value="'.$employee->email.'"', false);
    }

    public function test_employee_can_be_updated_keeping_own_code_and_email(): void
    {
        $employee = Employee::factory()->create($this->payload());

        $this->put(route('employees.update', $employee), $this->payload(['designation' => 'Senior Accountant', 'status' => 'on_notice']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertSame('Senior Accountant', $employee->designation);
        $this->assertSame('on_notice', $employee->status->value);
    }

    public function test_removing_an_employee_archives_the_record(): void
    {
        $employee = Employee::factory()->create();

        $this->delete(route('employees.destroy', $employee))->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted($employee);
        $this->get(route('employees.show', $employee))->assertNotFound();
    }

    public function test_dashboard_counts_employees(): void
    {
        Employee::factory()->count(3)->create();

        $this->get('/dashboard')->assertOk()->assertViewHas('stats', fn ($stats) => $stats['employees'] === 3);
    }
}
