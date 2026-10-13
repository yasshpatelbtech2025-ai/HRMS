<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_deleted_records_can_be_restored_with_undo(): void
    {
        $employee = Employee::factory()->create();
        $this->delete(route('employees.destroy', $employee))->assertSessionHas('undo', route('employees.restore', $employee));
        $this->patch(route('employees.restore', $employee))->assertRedirect();
        $this->assertNotSoftDeleted($employee);

        $company = Company::factory()->create();
        $this->delete(route('company.destroy', $company))->assertSessionHas('undo');
        $this->patch(route('company.restore', $company));
        $this->assertNotSoftDeleted($company);

        $department = Department::factory()->create();
        $this->delete(route('departments.destroy', $department));
        $this->patch(route('departments.restore', $department));
        $this->assertNotSoftDeleted($department);

        $position = Position::factory()->create();
        $this->delete(route('positions.destroy', $position));
        $this->patch(route('positions.restore', $position));
        $this->assertNotSoftDeleted($position);
    }

    public function test_bulk_delete_and_bulk_undo(): void
    {
        [$a, $b, $keep] = Employee::factory()->count(3)->create();

        $response = $this->delete(route('employees.bulk-destroy'), ['ids' => [$a->uuid, $b->uuid]])
            ->assertSessionHas('status', '2 employees deleted.');
        $this->assertSoftDeleted($a);
        $this->assertSoftDeleted($b);
        $this->assertNotSoftDeleted($keep);

        $this->patch(session('undo'))->assertSessionHas('status', '2 employees restored.');
        $this->assertNotSoftDeleted($a);
        $this->assertNotSoftDeleted($b);
    }

    public function test_bulk_delete_needs_a_selection(): void
    {
        $this->delete(route('employees.bulk-destroy'), ['ids' => []])->assertSessionHasErrors('ids');
    }

    public function test_employees_can_be_filtered_by_missing_records_and_sorted(): void
    {
        Employee::factory()->create(['first_name' => 'Complete', 'aadhaar_number' => '123412341234', 'joining_date' => '2020-01-01']);
        Employee::factory()->create(['first_name' => 'Gappy', 'aadhaar_number' => null, 'joining_date' => '2024-01-01']);

        $this->get(route('employees.index', ['missing' => 'aadhaar']))->assertSee('Gappy')->assertDontSee('Complete');

        $this->get(route('employees.index', ['sort' => 'joining_date', 'dir' => 'desc']))
            ->assertSeeInOrder(['Gappy', 'Complete']);
        $this->get(route('employees.index', ['sort' => 'joining_date', 'dir' => 'asc']))
            ->assertSeeInOrder(['Complete', 'Gappy']);
    }

    public function test_unknown_sort_and_page_size_fall_back_safely(): void
    {
        Employee::factory()->create();

        $this->get(route('employees.index', ['sort' => 'password; drop table', 'per_page' => 9999]))->assertOk();
    }

    public function test_save_and_add_another_returns_to_a_blank_form_for_the_same_company(): void
    {
        $company = Company::factory()->create();

        $this->post(route('employees.store'), [
            'company_id' => $company->id, 'first_name' => 'Asha', 'joining_date' => '2024-04-01', 'status' => 'active', 'after' => 'new',
        ])->assertRedirect(route('employees.create', ['company_id' => $company->id]));
    }

    public function test_masked_numbers_are_stored_as_digits(): void
    {
        $company = Company::factory()->create();

        $this->post(route('employees.store'), [
            'company_id' => $company->id, 'first_name' => 'Asha', 'joining_date' => '2024-04-01', 'status' => 'active',
            'mobile' => '98765 43210', 'aadhaar_number' => '1234 5678 9012',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('employees', ['mobile' => '9876543210', 'aadhaar_number' => '123456789012']);
    }

    public function test_dashboard_only_shows_real_numbers_and_marks_the_rest(): void
    {
        Employee::factory()->count(2)->create(['uan_number' => null]);

        $this->get('/dashboard')
            ->assertSeeText('Needs attention')
            ->assertSeeText('employees without a UAN')
            ->assertSeeText('Not connected yet');
    }
}
