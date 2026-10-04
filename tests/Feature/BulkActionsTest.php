<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Models\Company;
use App\Models\ComplianceTask;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->company = Company::factory()->create();
    }

    public function test_employees_can_be_bulk_exported_activated_deactivated_and_deleted(): void
    {
        $officer = Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Officer', 'status' => 'active']);
        $a = Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Asha', 'status' => 'active']);
        $b = Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Bina', 'status' => 'active', 'reporting_officer_id' => $officer->id]);
        $c = Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Chirag', 'status' => 'active']);

        $this->get(route('employees.index'))->assertSee('x-data="bulkSelect"', false)->assertSee('Mark inactive')->assertSee('data-bulk-id', false);

        $csv = $this->get(route('employees.export', ['csv', 'ids' => [$a->uuid, $c->uuid]]))->assertOk()->streamedContent();
        $this->assertStringContainsString('Asha', $csv);
        $this->assertStringNotContainsString('Bina', $csv);

        $this->post(route('employees.bulk'), ['ids' => [$a->uuid, $c->uuid], 'action' => 'deactivate'])->assertSessionHas('status', '2 employees are now inactive.');
        $this->assertSame(EmployeeStatus::Inactive, $a->fresh()->status);
        $this->assertSame(EmployeeStatus::Active, $b->fresh()->status);
        $this->post(route('employees.bulk'), ['ids' => [$a->uuid], 'action' => 'activate'])->assertSessionHas('status', '1 employee is now active.');

        // The officer stays while Bina still reports to them; deleting both together is fine.
        $this->post(route('employees.bulk'), ['ids' => [$officer->uuid, $a->uuid], 'action' => 'delete'])
            ->assertSessionHas('status', '1 employee deleted. 1 kept: reporting officer of other employees.')
            ->assertSessionHas('undo');
        $this->assertSoftDeleted($a);
        $this->assertNotSoftDeleted($officer);

        $response = $this->post(route('employees.bulk'), ['ids' => [$officer->uuid, $b->uuid], 'action' => 'delete']);
        $this->assertSoftDeleted($officer);
        $this->assertSoftDeleted($b);

        // Undo restores the whole batch.
        $this->post($response->getSession()->get('undo'))->assertSessionHas('status', 'Restored 2 records.');
        $this->assertNotSoftDeleted($officer);
        $this->assertNotSoftDeleted($b);

        $this->post(route('employees.bulk'), ['ids' => [], 'action' => 'delete'])->assertSessionHasErrors('ids');
        $this->post(route('employees.bulk'), ['ids' => [$a->uuid], 'action' => 'promote'])->assertSessionHasErrors('action');
    }

    public function test_compliance_tasks_can_be_bulk_completed_reopened_and_deleted(): void
    {
        $tasks = collect(['One', 'Two', 'Three'])->map(fn ($t) => ComplianceTask::create([
            'company_id' => $this->company->id, 'type' => 'custom', 'title' => $t, 'category' => 'HR', 'department' => 'General', 'due_on' => '2026-10-10',
        ]));
        $ids = $tasks->take(2)->pluck('uuid')->all();

        $this->get(route('compliance.to-do', ['status' => 'all']))->assertSee('data-bulk-id', false)->assertSee('Reopen');

        $this->post(route('compliance.tasks.bulk'), ['ids' => $ids, 'action' => 'complete'])->assertSessionHas('status', '2 tasks marked complete.');
        $this->assertSame(2, ComplianceTask::whereNotNull('completed_at')->where('completed_by', $this->user->id)->count());

        $this->post(route('compliance.tasks.bulk'), ['ids' => $ids, 'action' => 'reopen'])->assertSessionHas('status', '2 tasks reopened.');
        $this->assertSame(0, ComplianceTask::whereNotNull('completed_at')->count());

        $undo = $this->post(route('compliance.tasks.bulk'), ['ids' => $ids, 'action' => 'delete'])->assertSessionHas('status', '2 tasks deleted.')->getSession()->get('undo');
        $this->assertSame(1, ComplianceTask::where('type', 'custom')->count());
        $this->post($undo);
        $this->assertSame(3, ComplianceTask::where('type', 'custom')->count());
    }
}
