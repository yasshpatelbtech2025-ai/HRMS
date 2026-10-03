<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ComplianceFormValue;
use App\Models\ComplianceRegisterEntry;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\PunchProcessor;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ComplianceFormsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-07 12:00:00');
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        $parent = Company::factory()->create(['establishment_name' => 'Srympex']);
        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry', 'parent_company_id' => $parent->id, 'district' => 'Bharuch']);
        $shift = Shift::factory()->for($this->company)->create(['name' => 'General', 'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']], 'ot_after_hours' => 9]);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'shift_id' => $shift->id, 'first_name' => 'Gopal', 'last_name' => 'Bhat', 'employee_code' => '0001',
            'gender' => 'male', 'date_of_birth' => '1994-09-01', 'date_of_joining' => '2026-07-01', 'weekly_leave_day' => 'sunday',
            'pay_frequency' => 'daily', 'daily_rate' => 959.29, 'uan_code' => '101209880378', 'works_on_hazardous_process' => true,
        ]);
        $this->employee->familyMembers()->create(['relation' => 'father', 'full_name' => 'Rooplal Rao Bhat', 'is_nominee' => true, 'date_of_birth' => '1960-01-01']);

        foreach (['2026-09-01' => ['09:00', '18:00'], '2026-09-02' => ['09:00', '20:00']] as $date => [$in, $out]) {
            foreach (['in' => $in, 'out' => $out] as $type => $time) {
                $this->employee->attendances()->create(['company_id' => $this->company->id, 'punched_at' => "{$date} {$time}", 'work_date' => $date, 'type' => $type]);
            }
        }
        app(PunchProcessor::class)->process($this->employee->fresh('shift'), CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-09-02'));
        SalaryAdvance::create(['company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'amount' => 2000, 'salary_month' => '2026-09-01', 'advance_date' => '2026-09-03']);
        $this->post(route('payroll.generate'), ['company_id' => $this->company->id, 'salary_month' => '2026-09']);
    }

    private function form(string $form, array $query = []): TestResponse
    {
        return $this->get(route('compliance.forms.show', [$this->company, $form] + $query));
    }

    public function test_forms_page_lists_acts_and_contract_labour_forms(): void
    {
        $this->get(route('compliance.index', ['company' => $this->company->uuid]))
            ->assertOk()
            ->assertSeeInOrder(['To Do List', 'Forms', 'Company compliance', 'PI Industry'])
            ->assertSeeInOrder(['CONTRACT LABOUR ACT 1970', 'FACTORY ACT 1948', 'EMPLOYMENT EXCHANGE ACT 1959', 'PAYMENT OF BONUS ACT 1965', 'PT ACT 1976',
                'PF ACT 1952', 'WC ACT 1923', 'LABOUR WALFARE FUND ACT', 'ESIC ACT 1948', 'MATERNITY ACT 1961', 'POLICE VERIFICATION'])
            ->assertSeeInOrder(['CONTRACT LABOUR (REGULATION and ABOLITION) ACT 1970', 'Form A - Register of workman employed by contractor', 'Form E - Leave with Wage Register',
                'coming soon', 'FORM 23 - OVERTIME REGISTER', 'FORM V - LICENCE APPLY']);

        $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => 'factory-act']))
            ->assertOk()->assertSee('The forms for this act will be added once their pages are shared.');
    }

    public function test_every_built_form_shows_prints_and_downloads(): void
    {
        $september = ['month' => '2026-09'];
        $forms = [
            'form-a' => [$september, ['FORM A', 'EMPLOYEE REGISTER', 'Srympex', 'Sep, 2026', '0001', 'Gopal Bhat', 'Male', 'Rooplal Rao Bhat', '01-09-1994', 'INDIAN', '01-07-2026', '101209880378']],
            'form-b' => [$september, ['Register Of Wages', 'Gopal Bhat', 'Rooplal Rao Bhat', '959.29', '2']],
            'form-c' => [$september, ['FORM C', '0001', 'Gopal Bhat', 'Advance', 'Salary advance', '03-09-2026', '2,000.00']],
            'form-d' => [$september, ['Muster Roll', 'September 2026', 'Gopal Bhat', 'P', 'P', 'LOP']],
            'form-14' => [[], ['Employment Card', 'Gopal Bhat', '0001', '959.29', 'Daily', '01/07/2026 to Till Date']],
            'form-13' => [[], ['Register of Workmen Employed by Contractor', 'Gopal Bhat', 'Male. 32', 'Rooplal Rao Bhat', '01-07-2026']],
            'form-16' => [$september, ['Muster Roll', 'Gopal Bhat', 'P']],
            'form-17' => [$september, ['Register of wages', 'Gopal Bhat', '0001', '2']],
            'form-18' => [$september, ['Register of Wages-cum-Muster roll', 'Gopal Bhat', 'P']],
            'form-20' => [$september, ['Register of deductions for Damage or Loss', 'No deductions for damage or loss in September 2026', 'Add Row']],
            'form-21' => [$september, ['Register of Fines', 'No fines in September 2026']],
            'form-22' => [$september, ['Register of Advances', 'Gopal Bhat', '03-09-2026 — 2,000.00', 'Salary advance']],
            'form-23' => [$september, ['Register of Overtime', 'Gopal Bhat', '2nd -- 2 hrs', '2']],
            'form-25-half-yearly' => [['month' => 'december', 'year' => 2026], ['Form No. 25', 'Half Yearly Return', 'December, 2026', 'PI Industry', 'Srympex', 'Bharuch', '2']],
            'bonus-form-d' => [['year' => 2026], ['FORM D', '31st March 2027', 'PI Industry']],
            'gratuity-form-f' => [[], ['NOMINATION', 'Gopal Bhat', 'Rooplal Rao Bhat', 'Father', '100%', 'STATEMENT']],
        ];

        foreach ($forms as $form => [$query, $see]) {
            $this->form($form, $query)->assertOk()->assertSeeInOrder(['Print', 'Download PDF'])->assertSeeInOrder($see);
            $this->form($form, $query + ['output' => 'print'])->assertOk()->assertSee('window.print()', false)->assertDontSee('Add Row');
            $pdf = $this->form($form, $query + ['output' => 'pdf'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $pdf->getContent(), "{$form} PDF");
        }

        $this->form('form-e')->assertNotFound();
    }

    public function test_print_sets_the_page_orientation(): void
    {
        $this->form('form-a', ['month' => '2026-09', 'output' => 'print'])->assertSee('size: A4 landscape', false);
        $this->form('form-14', ['output' => 'print'])->assertSee('size: A4 portrait', false);
    }

    public function test_rows_can_be_added_to_and_removed_from_registers(): void
    {
        $this->post(route('compliance.forms.entries', [$this->company, 'form-21']), ['month' => '2026-09', 'rows' => [
            ['employee' => $this->employee->uuid, 'particular' => 'Late reporting', 'date' => '2026-09-10', 'amount' => 50, 'show_cause' => 'Yes', 'heard_by' => 'HR Head'],
        ]])->assertSessionHasNoErrors();

        $entry = ComplianceRegisterEntry::sole();
        $this->form('form-21', ['month' => '2026-09'])->assertSeeInOrder(['Gopal Bhat', 'Rooplal Rao Bhat', 'Late reporting', '10-09-2026', 'Yes', 'HR Head', '50.00']);
        $this->form('form-21', ['month' => '2026-08'])->assertSee('No fines in August 2026');

        $this->post(route('compliance.forms.entries', [$this->company, 'form-c']), ['month' => '2026-09', 'rows' => [['employee' => $this->employee->uuid]]])
            ->assertSessionHasErrors('rows.0.type');
        $this->post(route('compliance.forms.entries', [$this->company, 'form-a']), ['month' => '2026-09', 'rows' => []])->assertNotFound();

        $this->delete(route('compliance.entries.destroy', $entry))->assertSessionHas('status');
        $this->assertModelMissing($entry);
    }

    public function test_return_fields_and_signed_copy_are_saved(): void
    {
        $this->post(route('compliance.forms.values', [$this->company, 'form-25-half-yearly']), [
            'period' => '2026-12', 'raw_data' => [2 => 'Chlorine', 9 => '2027-01-05'], 'doc' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $value = ComplianceFormValue::sole();
        $this->assertSame('form-25', $value->form);
        $this->assertSame('Chlorine', $value->value(2));
        Storage::disk('local')->assertExists($value->signed_copy_path);

        $this->form('form-25-half-yearly', ['month' => 'december', 'year' => 2026])->assertSee('value="Chlorine"', false)->assertSee('signed.pdf');
        $this->form('form-25-half-yearly', ['month' => 'december', 'year' => 2026, 'output' => 'print'])->assertSee('Chlorine')->assertSee('05-01-2027');
        $this->get(route('compliance.forms.signed-copy', [$this->company, 'form-25', '2026-12']))->assertOk();

        $this->post(route('compliance.forms.values', [$this->company, 'gratuity-form-f']), ['period' => 'bad period'])->assertSessionHasErrors('period');
    }
}
