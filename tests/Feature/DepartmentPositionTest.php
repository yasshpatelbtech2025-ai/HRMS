<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentPositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_pages_render(): void
    {
        $department = Department::factory()->create(['name' => 'Boiler']);
        $position = Position::factory()->create(['name' => 'Operator']);

        $this->get(route('departments.index'))->assertOk()->assertSee('Boiler');
        $this->get(route('departments.create'))->assertOk();
        $this->get(route('departments.edit', $department))->assertOk();
        $this->get(route('positions.index'))->assertOk()->assertSee('Operator');
        $this->get(route('positions.create'))->assertOk();
        $this->get(route('positions.edit', $position))->assertOk();
    }

    public function test_department_with_a_sub_department_in_the_same_company(): void
    {
        $company = Company::factory()->create();
        $parent = Department::factory()->for($company)->create();

        $this->post(route('departments.store'), ['company_id' => $company->id, 'name' => 'Sub', 'parent_id' => $parent->id])
            ->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', ['name' => 'Sub', 'parent_id' => $parent->id]);

        $foreign = Department::factory()->create();
        $this->post(route('departments.store'), ['company_id' => $company->id, 'name' => 'Bad', 'parent_id' => $foreign->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_department_in_use_cannot_be_deleted(): void
    {
        $department = Department::factory()->create();
        Employee::factory()->create(['company_id' => $department->company_id, 'department_id' => $department->id]);

        $this->delete(route('departments.destroy', $department))->assertSessionHas('error');
        $this->assertNotSoftDeleted($department);

        $empty = Department::factory()->create();
        $this->delete(route('departments.destroy', $empty))->assertRedirect(route('departments.index'));
        $this->assertSoftDeleted($empty);
    }

    public function test_position_names_are_unique_and_in_use_positions_are_protected(): void
    {
        $position = Position::factory()->create(['name' => 'Supervisor']);

        $this->post(route('positions.store'), ['name' => 'Supervisor'])->assertSessionHasErrors('name');
        $this->put(route('positions.update', $position), ['name' => 'Supervisor'])->assertRedirect(route('positions.index'));

        Employee::factory()->create(['position_id' => $position->id]);
        $this->delete(route('positions.destroy', $position))->assertSessionHas('error');
        $this->assertNotSoftDeleted($position);
    }
}
