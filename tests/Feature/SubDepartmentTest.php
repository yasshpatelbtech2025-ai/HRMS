<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubDepartmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_redirected(): void
    {
        auth()->logout();
        $this->get(route('employees.departments'))->assertRedirect('/login');
    }

    public function test_list_shows_tabs_columns_and_rows(): void
    {
        $company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        $department = Department::factory()->for($company)->create(['name' => 'Utilities']);
        SubDepartment::factory()->for($department)->create(['company_id' => $company->id, 'name' => 'Boiler', 'is_active' => false]);

        $this->get(route('employees.departments'))
            ->assertOk()
            ->assertSeeInOrder(['Employee', 'Department', 'Positions', 'Employee id card', 'Separation employees list', 'Sub department list'])
            ->assertSeeInOrder(['Sl', 'Company name', 'Sub department name', 'Department name', 'Status', 'Action'])
            ->assertSeeInOrder(['PI Industry', 'Boiler', 'Utilities', 'Inactive']);
    }

    public function test_typing_a_new_department_creates_it_for_the_company(): void
    {
        $company = Company::factory()->create();

        $this->post(route('employees.departments.store'), [
            'department' => 'Utilities', 'company_id' => $company->id, 'name' => 'Boiler', 'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $department = Department::where('company_id', $company->id)->where('name', 'Utilities')->sole();
        $this->assertDatabaseHas('sub_departments', ['department_id' => $department->id, 'company_id' => $company->id, 'name' => 'Boiler', 'is_active' => true]);
    }

    public function test_an_existing_department_is_reused_ignoring_case(): void
    {
        $department = Department::factory()->create(['name' => 'Utilities']);

        $this->post(route('employees.departments.store'), [
            'department' => 'utilities ', 'company_id' => $department->company_id, 'name' => 'Ro', 'is_active' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Department::count());
        $this->assertDatabaseHas('sub_departments', ['department_id' => $department->id, 'name' => 'Ro', 'is_active' => false]);
    }

    public function test_required_fields_and_duplicate_names_are_rejected(): void
    {
        $this->post(route('employees.departments.store'), [])
            ->assertSessionHasErrors(['department', 'company_id', 'name', 'is_active']);

        $existing = SubDepartment::factory()->create(['name' => 'Boiler']);

        $this->post(route('employees.departments.store'), [
            'department' => $existing->department->name, 'company_id' => $existing->company_id, 'name' => 'BOILER', 'is_active' => 1,
        ])->assertSessionHasErrors('name');
    }

    public function test_sub_department_can_be_updated_and_deleted(): void
    {
        $subDepartment = SubDepartment::factory()->create(['name' => 'Boiler']);

        $this->put(route('employees.departments.update', $subDepartment), [
            'department' => $subDepartment->department->name, 'company_id' => $subDepartment->company_id, 'name' => 'Boiler House', 'is_active' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sub_departments', ['id' => $subDepartment->id, 'name' => 'Boiler House', 'is_active' => false]);

        $this->delete(route('employees.departments.destroy', $subDepartment))->assertSessionHas('status');
        $this->assertSoftDeleted($subDepartment);
    }

    public function test_search_and_export(): void
    {
        SubDepartment::factory()->create(['name' => 'Boiler']);
        SubDepartment::factory()->create(['name' => 'Packing']);

        $this->get(route('employees.departments', ['search' => 'boil']))->assertSee('Showing 1 to 1 of 1 entries');

        $csv = $this->get(route('employees.departments.export', ['format' => 'csv', 'search' => 'boil']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Sub department name', $csv);
        $this->assertStringContainsString('Boiler', $csv);
        $this->assertStringNotContainsString('Packing', $csv);

        $xlsx = $this->get(route('employees.departments.export', ['format' => 'xlsx']))->assertOk();
        $this->assertStringStartsWith('PK', $xlsx->streamedContent());

        $this->get(route('employees.departments.export', ['format' => 'docx']))->assertNotFound();
    }
}
