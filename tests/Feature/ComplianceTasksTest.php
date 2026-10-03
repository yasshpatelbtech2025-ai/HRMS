<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ComplianceFormValue;
use App\Models\ComplianceTask;
use App\Models\Employee;
use App\Models\MedicalExamination;
use App\Models\Payslip;
use App\Models\SalarySheet;
use App\Models\User;
use App\Services\Compliance\ComplianceTasks;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceTasksTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-07 12:00:00');
        $this->user = User::factory()->create(['name' => 'Asha Patel']);
        $this->actingAs($this->user);
        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry', 'created_at' => '2026-01-01']);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'first_name' => 'Gopal', 'last_name' => 'Bhat', 'employee_code' => '0001',
            'date_of_joining' => '2026-07-01', 'created_at' => '2026-09-02', 'works_on_hazardous_process' => true,
        ]);
    }

    private function sync(): void
    {
        app(ComplianceTasks::class)->sync($this->company);
    }

    public function test_tasks_are_created_for_employees_months_and_half_years(): void
    {
        // August payroll deducted PF only.
        $sheet = SalarySheet::create(['company_id' => $this->company->id, 'month' => '2026-08-01', 'generated_at' => now()]);
        Payslip::create(['salary_sheet_id' => $sheet->id, 'company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'month' => '2026-08-01',
            'pay_frequency' => 'monthly', 'attendance' => [], 'earnings' => [], 'employer' => [], 'gross_earnings' => 10000, 'total_deductions' => 1200, 'net_pay' => 8800,
            'deductions' => [['key' => 'pf', 'label' => 'PF', 'amount' => 1200], ['key' => 'esi', 'label' => 'ESI', 'amount' => 0]]]);

        $this->sync();
        $this->sync(); // idempotent

        $tasks = ComplianceTask::all();
        $byType = $tasks->groupBy('type')->map->count();
        $this->assertSame(['form-33' => 1, 'health-register' => 2, 'id-card' => 1, 'pf-return' => 1, 'police-verification' => 1, 'salary' => 3], $byType->sortKeys()->all());

        // Joining tasks: 15 days after the employee was added (2 Sep).
        $this->assertSame('2026-09-17', $tasks->firstWhere('type', 'id-card')->due_on->toDateString());
        // Monthly: July, August, September; salary by the 7th, PF by the 15th of the next month.
        $this->assertEqualsCanonicalizing(['2026-07', '2026-08', '2026-09'], $tasks->where('type', 'salary')->pluck('period')->all());
        $pf = $tasks->firstWhere('type', 'pf-return');
        $this->assertSame(['PF Return For the Month August 2026', '2026-09-15'], [$pf->title, $pf->due_on->toDateString()]);
        // August's salary was generated, so its task completed itself.
        $this->assertNotNull($tasks->first(fn ($t) => $t->type === 'salary' && $t->period === '2026-08')->completed_at);
        $this->assertNull($tasks->first(fn ($t) => $t->type === 'salary' && $t->period === '2026-09')->completed_at);
        // Half years: Jan-Jun (due 30 Jun) and Jul-Dec (due 31 Dec).
        $this->assertEqualsCanonicalizing(['2026-06-30', '2026-12-31'], $tasks->where('type', 'health-register')->map(fn ($t) => $t->due_on->toDateString())->values()->all());
    }

    public function test_tasks_complete_themselves_when_the_records_show_they_are_done(): void
    {
        $this->sync();
        $this->assertSame(0, ComplianceTask::whereNotNull('completed_at')->count());

        $this->employee->update(['id_card_assigned_on' => '2026-09-20']);
        MedicalExamination::create(['company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'examined_on' => '2026-08-10']);
        ComplianceFormValue::create(['company_id' => $this->company->id, 'form' => 'police-verification', 'period' => 'employee:'.$this->employee->id, 'signed_copy_path' => 'x.pdf', 'signed_copy_name' => 'x.pdf']);
        $this->sync();

        $done = ComplianceTask::whereNotNull('completed_at')->get();
        $this->assertEqualsCanonicalizing(['form-33', 'id-card', 'police-verification', 'health-register'], $done->pluck('type')->all());
        $this->assertSame('2026-H2', $done->firstWhere('type', 'health-register')->period);
        $this->assertNull($done->first()->completed_by);
    }

    public function test_to_do_list_shows_filters_and_handles_tasks(): void
    {
        $this->get(route('compliance.to-do'))->assertOk()
            ->assertSeeInOrder(['To Do List', 'Forms', 'Total tasks', 'Overdue', 'Due this week', 'Completed', 'Upcoming'])
            ->assertSeeInOrder(['Fitness Certificate of New Employment (Form 33) Maintain', 'Gopal Bhat', 'Open', 'Complete', 'Delete', 'EHS', 'Factory Act', 'PI Industry', '17 Sep 2026', '20 days overdue', 'High', 'Overdue', 'Unassigned']);

        $task = ComplianceTask::where('type', 'id-card')->sole();
        $this->get(route('compliance.to-do', ['department' => 'Document']))->assertSee('I-Card (Identity Card)')->assertDontSee('Fitness Certificate of New Employment (Form 33) Maintain</p>', false);
        $this->get(route('compliance.to-do', ['status' => 'upcoming']))->assertSee('Health Register')->assertDontSee('I-Card (Identity Card)</p>', false);

        $this->patch(route('compliance.tasks.update', $task), ['assigned_to' => $this->user->id])->assertSessionHasNoErrors();
        $this->post(route('compliance.tasks.complete', $task))->assertSessionHas('status');
        $this->assertSame([$this->user->id, $this->user->id], [$task->fresh()->assigned_to, $task->fresh()->completed_by]);
        $this->get(route('compliance.to-do', ['status' => 'completed']))->assertSee('I-Card (Identity Card)')->assertSee('Asha Patel')->assertSee('Reopen');
        // The list opens on pending tasks; "all" includes completed ones.
        $this->get(route('compliance.to-do'))->assertDontSee('I-Card (Identity Card)</p>', false)->assertSee('<option value="pending" selected', false);
        $this->get(route('compliance.to-do', ['status' => 'all']))->assertSee('I-Card (Identity Card)</p>', false);
        $this->post(route('compliance.tasks.reopen', $task));
        $this->assertNull($task->fresh()->completed_at);

        // A deleted generated task stays deleted.
        $this->delete(route('compliance.tasks.destroy', $task))->assertSessionHas('status');
        $this->sync();
        $this->assertSame(0, ComplianceTask::where('type', 'id-card')->count());

        $this->post(route('compliance.tasks.store'), [
            'company' => $this->company->uuid, 'title' => 'Renew factory licence', 'category' => 'Legal', 'department' => 'Factory Act',
            'due_on' => '2026-10-10', 'priority' => 'medium', 'notes' => 'Form 2',
        ])->assertSessionHasNoErrors();
        $this->get(route('compliance.to-do', ['type' => 'custom']))->assertSee('Renew factory licence')->assertSee('Due soon')->assertSee('Medium');
        $this->post(route('compliance.tasks.store'), ['company' => $this->company->uuid])->assertSessionHasErrors(['title', 'due_on']);
    }

    public function test_dashboard_shows_compliance_health(): void
    {
        $this->sync();
        ComplianceTask::where('type', 'form-33')->update(['completed_at' => now()]);

        // Past due: Form 33, I-card, police verification, July and August salary and the Jan-Jun health register (September's salary is due today, not late): 1 of 6 done.
        $this->get(route('dashboard'))->assertOk()
            ->assertSeeInOrder(['Compliance Health', '17%', 'Overdue', 'This week', 'Upcoming', 'Needs attention', 'Health Register (Half-Yearly) (Form 32) Maintain']);
    }
}
