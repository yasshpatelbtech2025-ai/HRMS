<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdCardAndSeparationTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        $this->employee = Employee::factory()->create([
            'company_id' => Company::factory(), 'first_name' => 'Kishan', 'last_name' => 'Rao', 'date_of_joining' => '2025-01-01',
        ]);
    }

    public function test_id_card_can_be_assigned_edited_and_removed(): void
    {
        $this->get(route('employees.id-cards'))->assertOk()->assertSee('Assign id card')->assertSee('Not Assigned');

        $this->post(route('employees.id-cards.store'), ['employee' => $this->employee->uuid, 'assigned_on' => '2026-09-01'])
            ->assertSessionHasNoErrors()->assertSessionHas('status');
        $this->assertSame('2026-09-01', $this->employee->fresh()->id_card_assigned_on->toDateString());

        // Already has a card, so it can't be assigned again from "Assign id card".
        $this->post(route('employees.id-cards.store'), ['employee' => $this->employee->uuid, 'assigned_on' => '2026-09-02'])
            ->assertSessionHasErrors('employee');

        $this->get(route('employees.id-cards'))->assertSee('01 Sep 2026')->assertSee('Edit id card');

        $this->put(route('employees.id-cards.update', $this->employee), ['assigned_on' => '2026-09-15'])->assertSessionHasNoErrors();
        $this->assertSame('2026-09-15', $this->employee->fresh()->id_card_assigned_on->toDateString());

        $this->put(route('employees.id-cards.update', $this->employee), ['assigned_on' => '2024-12-31'])->assertSessionHasErrors('assigned_on');
        $this->put(route('employees.id-cards.update', $this->employee), ['assigned_on' => today()->addDay()->toDateString()])->assertSessionHasErrors('assigned_on');

        $this->delete(route('employees.id-cards.destroy', $this->employee))->assertSessionHas('status');
        $this->assertNull($this->employee->fresh()->id_card_assigned_on);
    }

    public function test_separation_can_be_added_edited_and_removed(): void
    {
        $this->get(route('employees.separations'))->assertOk()->assertSee('Add separation')->assertSee('No separated employees.');

        $this->post(route('employees.separations.store'), ['employee' => $this->employee->uuid, 'separation_date' => '2026-09-30'])
            ->assertSessionHasNoErrors()->assertSessionHas('status');
        $this->assertSame('2026-09-30', $this->employee->fresh()->separation_date->toDateString());

        $this->get(route('employees.separations'))->assertSee('Kishan Rao')->assertSee('Edit separation');

        $this->post(route('employees.separations.store'), ['employee' => $this->employee->uuid, 'separation_date' => '2026-10-01'])
            ->assertSessionHasErrors('employee');

        $this->put(route('employees.separations.update', $this->employee), ['separation_date' => '2026-10-31'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-31', $this->employee->fresh()->separation_date->toDateString());

        $this->put(route('employees.separations.update', $this->employee), ['separation_date' => '2024-01-01'])->assertSessionHasErrors('separation_date');
        $this->put(route('employees.separations.update', $this->employee), ['separation_date' => ''])->assertSessionHasErrors('separation_date');

        $this->delete(route('employees.separations.destroy', $this->employee))->assertSessionHas('status');
        $this->assertNull($this->employee->fresh()->separation_date);
    }

    public function test_failed_edit_reopens_that_rows_modal(): void
    {
        $this->employee->update(['separation_date' => '2026-09-30']);

        $this->from(route('employees.separations'))->followingRedirects()
            ->put(route('employees.separations.update', $this->employee), ['_modal' => 'edit-separation-'.$this->employee->uuid, 'separation_date' => '2024-01-01'])
            ->assertSee('cannot be before Kishan Rao joined');
    }
}
