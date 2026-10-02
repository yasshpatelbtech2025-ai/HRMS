<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_manage_departments(): void
    {
        $company = Company::factory()->create();

        $this->post(route('company.departments.store', $company), ['name' => 'HR'])->assertRedirect('/login');
        $this->assertDatabaseCount('departments', 0);
    }

    public function test_departments_are_listed_on_the_company_edit_page(): void
    {
        $company = Company::factory()->create();
        Department::factory()->for($company)->create(['name' => 'Quality Control']);

        $this->actingAs(User::factory()->create())
            ->get(route('company.edit', $company))
            ->assertOk()
            ->assertSeeText('Quality Control');
    }

    public function test_department_can_be_added_once_per_company(): void
    {
        $this->actingAs(User::factory()->create());
        [$first, $second] = Company::factory()->count(2)->create();

        $this->post(route('company.departments.store', $first), ['name' => 'Finance'])
            ->assertRedirect(route('company.edit', $first).'#departments');
        $this->post(route('company.departments.store', $first), ['name' => 'Finance'])
            ->assertSessionHasErrorsIn('department', 'name');
        $this->post(route('company.departments.store', $second), ['name' => 'Finance'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $first->departments()->count());
        $this->assertSame(1, $second->departments()->count());
    }

    public function test_empty_department_can_be_removed(): void
    {
        $department = Department::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('company.departments.destroy', [$department->company, $department]))
            ->assertRedirect();

        $this->assertModelMissing($department);
    }

    public function test_department_with_employees_cannot_be_removed(): void
    {
        $department = Department::factory()->create();
        Employee::factory()->for($department->company)->for($department)->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('company.departments.destroy', [$department->company, $department]))
            ->assertSessionHas('error');

        $this->assertModelExists($department);
    }

    public function test_department_routes_are_scoped_to_their_company(): void
    {
        $department = Department::factory()->create();
        $other = Company::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('company.departments.destroy', [$other, $department]))
            ->assertNotFound();

        $this->assertModelExists($department);
    }

    public function test_company_with_employees_cannot_be_deleted(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('company.destroy', $employee->company))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($employee->company);
    }
}
