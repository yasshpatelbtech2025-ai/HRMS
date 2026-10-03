<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\Position;
use App\Models\SalaryAdvance;
use App\Models\SalaryRevision;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PayrollInputsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'first_name' => 'Gopal', 'last_name' => 'Bhat', 'employee_code' => '0001',
            'position_id' => Position::factory()->for($this->company)->create(['name' => 'Operator'])->id,
            'pay_frequency' => 'daily', 'daily_rate' => 959.29,
        ]);
        foreach (['basic' => 15000, 'hra' => 3000, 'conveyance' => 1000] as $element => $monthly) {
            $this->employee->salaryElements()->create(['element' => $element, 'monthly' => $monthly, 'yearly' => $monthly * 12]);
        }
        $this->employee->salaryElements()->create(['element' => 'bonus', 'monthly' => 8.33, 'yearly' => 0]);
    }

    public function test_salary_advances(): void
    {
        $this->get(route('payroll.advances'))->assertOk()
            ->assertSeeInOrder(['Salary Generate', 'Manage Employee Salary', 'Salary Advance', 'Salary adjustments', 'Salary revision', 'Current Salary', 'Loan'])
            ->assertSeeInOrder(['Salary advanced list', 'Add salary advance', 'Sl', 'Employee name', 'Amount', 'Release amount', 'Salary month', 'Advance date', 'Attachment', 'Status', 'Action']);

        $this->post(route('payroll.advances.store'), [
            'employee' => $this->employee->uuid, 'amount' => 5000, 'salary_month' => '2026-10', 'advance_date' => '2026-10-01', 'is_active' => 1,
            'attachment' => UploadedFile::fake()->create('note.pdf', 20, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $advance = SalaryAdvance::sole();
        $this->assertSame('2026-10-01', $advance->salary_month->toDateString());
        $this->get(route('payroll.advances.attachment', $advance))->assertOk();
        $this->get(route('payroll.advances'))->assertSeeInOrder(['Gopal Bhat', '5,000.00', '0.00', '2026-10', '2026-10-01', 'View', 'Active']);

        $this->put(route('payroll.advances.update', $advance), ['employee' => $this->employee->uuid, 'amount' => 4000, 'salary_month' => '2026-11', 'advance_date' => '2026-10-01', 'is_active' => 0])
            ->assertSessionHasNoErrors();
        $this->assertEquals(4000, $advance->fresh()->amount);

        $this->assertStringContainsString('Gopal Bhat', $this->get(route('payroll.advances.export'))->streamedContent());
        $this->delete(route('payroll.advances.destroy', $advance))->assertSessionHas('status');
        $this->assertModelMissing($advance);
    }

    public function test_salary_adjustments_accept_new_titles(): void
    {
        $this->get(route('payroll.adjustments'))->assertOk()->assertSee('Canteen Allowance')->assertSee('ARREARS');

        $this->post(route('payroll.adjustments.store'), ['employee' => $this->employee->uuid, 'title' => 'Uniform Recovery', 'amount' => 250, 'salary_month' => '2026-10', 'type' => 'deduction'])
            ->assertSessionHasNoErrors();
        $this->post(route('payroll.adjustments.store'), ['employee' => $this->employee->uuid, 'title' => 'X', 'amount' => 10, 'salary_month' => '2026-10', 'type' => 'bonus'])
            ->assertSessionHasErrors('type');

        $this->get(route('payroll.adjustments'))
            ->assertSeeInOrder(['Gopal Bhat', 'Uniform Recovery', '250.00', '2026-10', 'Deduction'])
            ->assertSee('<option value="Uniform Recovery">', false);
    }

    public function test_loans_work_out_repayment_and_number_themselves(): void
    {
        $boss = Employee::factory()->create(['company_id' => $this->company->id]);
        $outsider = Employee::factory()->create();

        $loan = ['employee' => $this->employee->uuid, 'permitted_by' => $boss->uuid, 'amount' => 10000, 'approved_date' => '2026-10-01',
            'repayment_start' => '2026-11', 'interest_rate' => 12, 'installment_period' => 6, 'is_active' => 1];

        $this->post(route('payroll.loans.store'), ['permitted_by' => $outsider->uuid] + $loan)->assertSessionHasErrors('permitted_by');
        $this->post(route('payroll.loans.store'), $loan)->assertSessionHasNoErrors();

        $saved = Loan::sole();
        $this->assertSame('LN-0001', $saved->loan_no);
        $this->assertEquals(11200, $saved->repayment_amount, '10,000 + 12% flat interest');
        $this->assertEquals(1867, $saved->installment, '11,200 / 6, rounded');

        $this->assertSame(0.0, $saved->installmentFor(CarbonImmutable::create(2026, 10)), 'Nothing due before repayment starts');
        $this->assertEquals(1867, $saved->installmentFor(CarbonImmutable::create(2026, 11)));
        $saved->update(['installments_cleared' => 5]);
        $this->assertEquals(1865, $saved->installmentFor(CarbonImmutable::create(2027, 4)), 'The last installment is what is left');

        $this->get(route('payroll.loans'))->assertSeeInOrder(['Loan list', 'PI Industry', 'Gopal Bhat', 'LN-0001', '10,000.00', '12%', '6', '5', '11,200.00']);
        $this->delete(route('payroll.loans.destroy', $saved))->assertSessionHas('error');
    }

    public function test_salary_revision_applies_from_its_month(): void
    {
        $this->get(route('payroll.revisions.create', $this->employee))->assertOk()->assertSee('Add salary revision')->assertSee('15,000.00');

        $this->post(route('payroll.revisions.store', $this->employee), [
            'effective_from' => '2026-12', 'salary' => ['basic' => ['monthly' => 16500, 'yearly' => 198000], 'hra' => ['monthly' => 3300], 'bonus' => ['monthly' => 8.33]],
        ])->assertSessionHasNoErrors();

        $revision = SalaryRevision::sole();
        $this->assertSame('pending', $revision->status);
        $this->assertEquals(228000, $revision->previous_yearly, '(15,000 + 3,000 + 1,000) × 12');
        $this->assertEquals(237600, $revision->revised_yearly, '(16,500 + 3,300) × 12');
        $this->assertSame(4.21, $revision->percentage());
        $this->assertEquals(15000, $this->employee->salaryElements()->where('element', 'basic')->value('monthly'), 'Not applied before December');

        $this->get(route('payroll.revisions', ['employee' => $this->employee->uuid]))
            ->assertSeeInOrder(['0001', 'Gopal Bhat', '2026-10-02', 'December 2026', '237,600.00', '+4.21%', 'Pending']);

        Carbon::setTestNow('2026-12-01 09:00:00');
        $this->get(route('payroll.revisions'))->assertOk();
        $this->assertSame('applied', $revision->fresh()->status);
        $this->assertEquals(16500, $this->employee->salaryElements()->where('element', 'basic')->value('monthly'));
        $this->assertNull($this->employee->salaryElements()->where('element', 'conveyance')->first(), 'Elements left out of the revision are removed');
    }

    public function test_revision_effective_now_applies_immediately_and_needs_basic(): void
    {
        $this->post(route('payroll.revisions.store', $this->employee), ['effective_from' => '2026-10', 'salary' => ['hra' => ['monthly' => 3300]]])
            ->assertSessionHasErrors('salary.basic.monthly');

        $this->post(route('payroll.revisions.store', $this->employee), ['effective_from' => '2026-10', 'salary' => ['basic' => ['monthly' => 17000]]])
            ->assertSessionHas('status', 'Salary revised for Gopal Bhat and applied.');
        $this->assertEquals(17000, $this->employee->salaryElements()->where('element', 'basic')->value('monthly'));
    }

    public function test_current_salary_list(): void
    {
        $this->get(route('payroll.current', ['company' => $this->company->uuid]))
            ->assertOk()
            ->assertSeeInOrder(['Current Salary List', 'Card No', 'Employee Name', 'Designation', 'Salary Type', 'Daily Rate', 'Basic', 'HRA', 'Conveyance', 'Bonus', 'CTC'])
            ->assertSeeInOrder(['0001', 'Gopal Bhat', 'Operator', 'Daily', '959.29', '15,000.00', '3,000.00', '1,000.00', '8.33%', '228,000.00']);
    }
}
