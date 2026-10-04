<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RecentlyDeletedTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
    }

    public function test_deleted_records_can_be_undone_and_restored(): void
    {
        $position = Position::create(['company_id' => $this->company->id, 'name' => 'Helper', 'details' => 'Duties']);

        $this->delete(route('employees.positions.destroy', $position))
            ->assertSessionHas('undo', route('recently-deleted.restore', ['position', $position->id]));
        $this->assertSoftDeleted($position);

        $this->get(route('configs.recently-deleted'))->assertOk()
            ->assertSeeInOrder(['Recently deleted', 'Position', 'Helper', 'PI Industry', 'Restore', 'Delete for good']);
        $this->get(route('configs.recently-deleted', ['type' => 'loan']))->assertSee('Nothing deleted');

        $this->post(route('recently-deleted.restore', ['position', $position->id]))->assertSessionHas('status', 'Restored: Helper.');
        $this->assertNotSoftDeleted($position);
    }

    public function test_names_are_unique_among_live_records_only(): void
    {
        $old = Position::create(['company_id' => $this->company->id, 'name' => 'Helper', 'details' => 'Duties']);
        $old->delete();

        // A deleted "Helper" does not block a new one...
        $this->post(route('employees.positions.store'), ['company_id' => $this->company->id, 'name' => 'Helper', 'details' => 'Duties', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, Position::count());

        // ...and the old one then can't be restored over it.
        $this->post(route('recently-deleted.restore', ['position', $old->id]))->assertSessionHas('error');
        $this->assertSoftDeleted($old);
    }

    public function test_records_can_be_deleted_for_good_except_employees_and_are_pruned_after_30_days(): void
    {
        $position = Position::create(['company_id' => $this->company->id, 'name' => 'Fitter', 'details' => 'Duties']);
        $position->delete();
        $this->delete(route('recently-deleted.destroy', ['position', $position->id]))->assertSessionHas('status');
        $this->assertModelMissing($position);

        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $employee->delete();
        $this->delete(route('recently-deleted.destroy', ['employee', $employee->id]))->assertForbidden();
        $this->get(route('configs.recently-deleted'))->assertSee('Kept until restored');

        $recent = Position::create(['company_id' => $this->company->id, 'name' => 'Welder', 'details' => 'Duties']);
        $expired = Position::create(['company_id' => $this->company->id, 'name' => 'Driver', 'details' => 'Duties']);
        $recent->delete();
        $expired->delete();
        $expired->forceFill(['deleted_at' => now()->subDays(31)])->save();
        $recent->forceFill(['deleted_at' => now()->subDays(29)])->save();

        Artisan::call('model:prune', ['--model' => [Position::class]]);
        $this->assertModelMissing($expired);
        $this->assertSoftDeleted($recent);
        $this->assertSoftDeleted($employee); // employees are never pruned
    }

    public function test_delete_buttons_ask_for_confirmation(): void
    {
        Position::create(['company_id' => $this->company->id, 'name' => 'Helper', 'details' => 'Duties']);

        $this->get(route('employees.positions'))->assertSee('data-confirm=', false)->assertDontSee('return confirm(', false);
        $this->get(route('dashboard'))->assertSee('x-data="confirmDialog"', false);
    }
}
