<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_list_shows_company_with_its_connected_company(): void
    {
        $parent = Company::factory()->create(['establishment_name' => 'Srympex']);
        $company = Company::factory()->create(['establishment_name' => 'PI Industry', 'parent_company_id' => $parent->id]);
        Position::factory()->for($company)->create(['name' => 'Helper']);

        $this->get(route('employees.positions'))
            ->assertOk()
            ->assertSeeInOrder(['Position list', 'Sl', 'Company name', 'Position name', 'Status', 'Action'])
            ->assertSeeInOrder(['PI Industry [Srympex]', 'Helper', 'Active']);
    }

    public function test_position_can_be_added(): void
    {
        $company = Company::factory()->create();

        $this->post(route('employees.positions.store'), [
            'company_id' => $company->id, 'name' => 'Welder', 'details' => 'Fabrication', 'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertDatabaseHas('positions', ['company_id' => $company->id, 'name' => 'Welder', 'details' => 'Fabrication', 'is_active' => true]);
    }

    public function test_names_are_unique_per_company_only(): void
    {
        $existing = Position::factory()->create(['name' => 'Helper']);
        $other = Company::factory()->create();

        $this->post(route('employees.positions.store'), ['company_id' => $existing->company_id, 'name' => 'Helper', 'details' => 'x', 'is_active' => 1])
            ->assertSessionHasErrors('name');
        $this->post(route('employees.positions.store'), ['company_id' => $other->id, 'name' => 'Helper', 'details' => 'x', 'is_active' => 1])
            ->assertSessionHasNoErrors();
    }

    public function test_update_cannot_move_a_position_to_another_company(): void
    {
        $position = Position::factory()->create();
        $other = Company::factory()->create();

        $this->put(route('employees.positions.update', $position), ['name' => 'Fitter', 'details' => 'Maintenance', 'is_active' => 0])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'name' => 'Fitter', 'is_active' => false]);

        $this->put(route('employees.positions.update', $position), ['company_id' => $other->id, 'name' => 'Fitter', 'details' => 'x', 'is_active' => 1])
            ->assertSessionHasErrors('company_id');
    }

    public function test_position_can_be_deleted(): void
    {
        $position = Position::factory()->create();

        $this->delete(route('employees.positions.destroy', $position))->assertSessionHas('status');
        $this->assertSoftDeleted($position);
    }
}
