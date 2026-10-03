<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ComplianceFormValue;
use App\Models\ComplianceRegisterEntry;
use App\Models\Employee;
use App\Models\MedicalExamination;
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
            ->assertOk()->assertSeeInOrder(['Form 15 - Register of adult workers', 'Form 5 - Certificate of Fitness', 'Form 25 - Half Yearly Return', 'Form 27-A',
                'Form 29', 'Form 30', 'Form 32 - Health register', 'Form 33', 'coming soon', 'Identity card register', 'Identity card'])
            ->assertSee(route('employees.id-cards'), false);

        $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => 'wc']))
            ->assertOk()->assertSeeInOrder(['WC ACT 1923', 'coming soon', 'WC ACT 1923 forms are coming soon.']);
        foreach (['labour-act' => 'LABOUR WALFARE FUND ACT', 'esic' => 'ESIC ACT 1948'] as $key => $label) {
            $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => $key]))->assertOk()->assertSee($label.' forms are coming soon.');
        }
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

    public function test_factory_act_forms_show_print_and_download(): void
    {
        $this->employee->update(['id_card_assigned_on' => '2026-07-05', 'ip_number' => '3712345678']);
        $exam = MedicalExamination::create([
            'company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'examined_on' => '2026-08-20', 'result' => 'fit',
            'certifying_surgeon' => 'Dr. Mehta', 'signs_symptoms' => 'NIL', 'extended_until' => '2027-08-19',
            'details' => ['identification_marks' => 'Mole on left cheek', 'hazardous_process' => 'Chlorination', 'tests' => 'Blood, X-ray normal'],
        ]);
        $this->post(route('compliance.forms.entries', [$this->company, 'form-29-accidents']), ['rows' => [
            ['employee' => $this->employee->uuid, 'date' => '2026-09-14', 'time' => '10:30', 'place' => 'Boiler house', 'cause' => 'Slipped', 'nature' => 'Sprain', 'days_absent' => 3],
        ]])->assertSessionHasNoErrors();

        $forms = [
            'form-15-adult-register' => [[], ['Register of Adult Workers', 'Gopal Bhat', '01-09-1994', 'Male', 'Rooplal Rao Bhat', '01-07-2026', 'General', 'N/A']],
            'id-card-register' => [[], ['IDENTITY CARD REGISTER', 'PI Industry', 'Gopal Bhat', '01-09-1994', '01-07-2026', '05-07-2026']],
            'form-29-accidents' => [['year' => 2026], ['Register of accidents', 'Gopal Bhat (0001)', '3712345678', '14-09-2026', '10:30', 'Boiler house', 'Slipped', 'Sprain', '3']],
            'form-5-fitness-young' => [[], ['FORM NO. 5', '0001', 'Gopal Bhat', 'Rooplal Rao Bhat', 'Mole on left cheek', 'Fit', 'adult', 'DR. MEHTA']],
            'form-27a-fitness' => [[], ['FORM NO. 27-A', 'CERTIFICATE OF FITNESS', 'Gopal Bhat', 'Rooplal Rao Bhat', '31', 'Fit', '20-08-2026', '19-08-2027', 'NIL']],
            'form-30-special-fitness' => [[], ['FORM NO. 30', 'lead compounds', 'Gopal Bhat', 'Mole on left cheek', '20-08-2026', '19-08-2027']],
            'form-32-health-register' => [[], ['FORM NO. 32', 'Health Register', '0001', 'Gopal Bhat', '32 Years', 'Chlorination', '20-08-2026', 'NIL', 'Blood, X-ray normal', 'Fit', 'Dr. Mehta']],
            'factory-form-25' => [['month' => 'june', 'year' => 2026], ['Form No. 25', 'Half Yearly Return']],
        ];

        foreach ($forms as $form => [$query, $see]) {
            $this->form($form, $query)->assertOk()->assertSeeInOrder(['Print', 'Download PDF'])->assertSeeInOrder($see);
            $this->form($form, $query + ['output' => 'print'])->assertOk()->assertSee('window.print()', false)->assertDontSee('Add examination')->assertDontSee('Add Row');
            $pdf = $this->form($form, $query + ['output' => 'pdf'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $pdf->getContent(), "{$form} PDF");
        }

        $this->form('form-27a-fitness')->assertSee('Examination : 20-08-2026')->assertSee('Add examination');
        $this->form('form-29-accidents', ['year' => 2025])->assertSee('No accidents recorded in 2025');
        $this->form('form-32-health-register', ['output' => 'print'])->assertSee('size: A4 landscape', false);
        $this->form('form-5-fitness-young', ['output' => 'print'])->assertSee('size: A4 portrait', false);
        $this->assertSame($exam->uuid, MedicalExamination::sole()->uuid);
    }

    public function test_medical_examinations_can_be_added_edited_and_deleted(): void
    {
        $this->post(route('compliance.examinations.store', $this->company), [
            '_modal' => 'examination-add', 'form' => 'form-27a-fitness', 'employee' => $this->employee->uuid, 'examined_on' => '2026-09-01',
            'result' => 'fit', 'certifying_surgeon' => 'Dr. Shah', 'details' => ['raw_materials' => 'Chlorine', 'unfit_on' => ''],
            'doc' => UploadedFile::fake()->create('cert.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors()->assertSessionHas('status');

        $exam = MedicalExamination::sole();
        $this->assertSame(['raw_materials' => 'Chlorine'], $exam->details);
        Storage::disk('local')->assertExists($exam->signed_copy_path);
        $this->get(route('compliance.examinations.signed-copy', $exam))->assertOk();
        $this->form('form-32-health-register', ['employee' => $this->employee->uuid, 'exam' => $exam->uuid])->assertSee('Chlorine')->assertSee('Edit');

        $this->put(route('compliance.examinations.update', $exam), ['examined_on' => '2026-09-02', 'result' => 'unfit', 'details' => ['withdrawal_period' => '3 months']])
            ->assertSessionHasNoErrors();
        $this->assertSame('unfit', $exam->fresh()->result);
        $this->assertSame('3 months', $exam->fresh()->detail('withdrawal_period'));

        $this->post(route('compliance.examinations.store', $this->company), ['_modal' => 'examination-add', 'employee' => $this->employee->uuid, 'result' => 'maybe'])
            ->assertSessionHasErrors(['examined_on', 'result']);
        $this->post(route('compliance.forms.entries', [$this->company, 'form-29-accidents']), ['rows' => [['employee' => $this->employee->uuid, 'date' => '2026-12-01']]])
            ->assertSessionHasErrors('rows.0.date');

        $path = $exam->fresh()->signed_copy_path;
        $this->delete(route('compliance.examinations.destroy', $exam))->assertSessionHas('status');
        $this->assertModelMissing($exam);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_employment_exchange_er1_counts_staff_and_saves_fields(): void
    {
        $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => 'employment-exchange-act']))->assertOk()->assertSee('ER-1 - Quarterly Return');
        Employee::factory()->create(['company_id' => $this->company->id, 'gender' => 'female', 'date_of_joining' => '2026-08-10']);

        // Jul - Sep 2026: nobody on 30 Jun (both joined later), one man and one woman on 30 Sep.
        $this->form('er-1-quarterly-return', ['quarter' => 3, 'year' => 2026])->assertOk()
            ->assertSeeInOrder(['FORM ER-1', 'quarter ended', 'Sep-2026', 'PI Industry', 'Jun-2026', 'Sep-2026', 'MEN', '0', '1', 'WOMEN', '0', '1', 'TOTAL', '0', '2', 'Save']);

        $this->post(route('compliance.forms.values', [$this->company, 'er-1-quarterly-return']), [
            'period' => '2026-09', 'raw_data' => ['nature_of_business' => 'Chemicals', 'vacancies_occurred' => '3', 'shortage_1_post' => 'Boiler operator'],
        ])->assertSessionHasNoErrors();

        $this->form('er-1-quarterly-return', ['quarter' => 3, 'year' => 2026, 'output' => 'print'])->assertOk()
            ->assertSee('Chemicals')->assertSee('Boiler operator')->assertSee('size: A4 portrait', false)->assertDontSee('Save');
        $this->form('er-1-quarterly-return', ['quarter' => 2, 'year' => 2026])->assertDontSee('Chemicals');
        $pdf = $this->form('er-1-quarterly-return', ['quarter' => 3, 'year' => 2026, 'output' => 'pdf'])->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->form('er-1-quarterly-return')->assertSee('Dec-2026')->assertSee('counted as on today');
    }

    public function test_payment_of_bonus_act_forms(): void
    {
        $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => 'payment-of-bonus-act']))->assertOk()
            ->assertSeeInOrder(['Form A - Allocable Surplus', 'Form B - Set-On / Set-Off Bonus', 'Form C - Details of Bonus', 'Form D - Annual Return']);

        // Form A: columns 6 and 7 are worked out from the saved figures.
        $this->post(route('compliance.forms.values', [$this->company, 'bonus-form-a']), ['period' => '2026-2027', 'raw_data' => [1 => '1000000', 2 => '100000', 4 => '50000', 8 => '510000']])
            ->assertSessionHasNoErrors();
        $this->form('bonus-form-a', ['year' => 2026, 'output' => 'print'])->assertOk()
            ->assertSeeInOrder(['FORM A', 'Computation of allocable surplus', '31st March 2027', '1,000,000.00', '150,000.00', '850,000.00', '510,000.00']);

        $this->post(route('compliance.forms.values', [$this->company, 'bonus-form-b']), ['period' => '2026-2027', 'raw_data' => [2 => '510000', 3 => '400000']])->assertSessionHasNoErrors();
        $this->form('bonus-form-b', ['year' => 2026])->assertOk()->assertSee('2026 - 2027')->assertSee('110,000.00 (set on)');

        // Form C: September's payslip, and the deductions typed in.
        $id = $this->employee->id;
        $this->post(route('compliance.forms.values', [$this->company, 'bonus-form-c']), ['period' => '2026-2027', 'raw_data' => ["{$id}_puja" => '100', "{$id}_tax" => '50', "{$id}_paid_on" => '2027-04-10']])
            ->assertSessionHasNoErrors();
        $this->form('bonus-form-c', ['year' => 2026])->assertOk()
            ->assertSeeInOrder(['FORM C', 'April 2026 to March 2027', 'No. of working days in the year', '2', 'Gopal Bhat', 'Rooplal Rao Bhat', 'Yes', '2', '8.33%', '150.00'])
            ->assertSee('Save');
        $this->form('bonus-form-c', ['year' => 2026, 'output' => 'print'])->assertOk()->assertSee('10-04-2027')->assertSee('size: A4 landscape', false);
        $this->form('bonus-form-c', ['year' => 2025])->assertOk()->assertSee('No employees in this accounting year');

        $this->form('payment-bonus-form-d', ['year' => 2026])->assertOk()->assertSee('31st March 2027');
        foreach (['bonus-form-a', 'bonus-form-b', 'bonus-form-c', 'payment-bonus-form-d'] as $form) {
            $this->assertStringStartsWith('%PDF', $this->form($form, ['year' => 2026, 'output' => 'pdf'])->assertOk()->getContent(), $form);
        }
    }

    public function test_professional_tax_form_5(): void
    {
        $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => 'pt']))->assertOk()->assertSee('Form 5 PT - Payment of Professional Tax');

        // September's payslip: one employee below ₹12,000, no PT deducted.
        $this->form('form-5-pt', ['month' => '2026-09'])->assertOk()
            ->assertSeeInOrder(['FORM NO. 5', 'September 2026', 'Below Rs. 12,000/-', '1', '1', '0', '0', '0.00', 'Rs. 12,000/- or more', '0', '-', '0', '200', '0.00', 'Total (A) Rs.', '0.00', 'Save']);
        $this->form('form-5-pt', ['month' => '2026-08'])->assertSee('No salary has been generated for August 2026 yet');

        $this->post(route('compliance.forms.values', [$this->company, 'form-5-pt']), [
            'period' => '2026-09', 'raw_data' => ['registration_no' => 'PRC-77', 'interest' => '40', 'cheque_no' => '004512', 'declarant' => 'R. Shah'],
        ])->assertSessionHasNoErrors();

        $this->form('form-5-pt', ['month' => '2026-09', 'output' => 'print'])->assertOk()
            ->assertSeeInOrder(['PRC-77', 'NOT APPLICABLE', 'Total tax and interest payable', '40.00', '004512', 'R. Shah'])->assertDontSee('Save');
        $this->assertStringStartsWith('%PDF', $this->form('form-5-pt', ['month' => '2026-09', 'output' => 'pdf'])->assertOk()->getContent());
    }

    public function test_pf_act_statement_and_claim_forms(): void
    {
        $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => 'pf']))->assertOk()
            ->assertSeeInOrder(['PF ACT - Monthly contribution statement', 'Form 10C', 'Form 19 - Claim Form']);

        $this->form('pf-act', ['month' => '2026-09'])->assertOk()
            ->assertSeeInOrder(['PF Contribution Statement', 'September 2026', '0001', 'Gopal Bhat', '101209880378', 'ADMINISTRATION CHARGE (AC 2)', 'EDLI CHARGE (AC 21)', 'TOTAL PF', 'AC 1'])
            ->assertSee('Excel')->assertSee('Upload statement');
        $this->form('pf-act', ['month' => '2026-08'])->assertSee('No salary has been generated for August 2026 yet');
        $xlsx = $this->form('pf-act', ['month' => '2026-09', 'output' => 'xlsx'])->assertOk();
        $this->assertStringContainsString('spreadsheetml', $xlsx->headers->get('Content-Type'));
        $this->assertStringContainsString('Gopal Bhat', $this->form('pf-act', ['month' => '2026-09', 'output' => 'csv'])->streamedContent());
        $this->form('form-5-pt', ['output' => 'xlsx'])->assertNotFound();

        $this->employee->update(['bank_name' => 'State Bank of India', 'account_number' => '30012345678', 'ifsc_code' => 'SBIN0001234', 'pan_card_no' => 'ABCDE1234F', 'phone' => '9876543210']);
        $this->post(route('compliance.forms.values', [$this->company, 'pf-form-19']), ['period' => 'employee:'.$this->employee->id, 'raw_data' => ['pf_account_no' => 'GJ/VAD/12345/678']])
            ->assertSessionHasNoErrors();

        $this->form('pf-form-19', ['employee' => $this->employee->uuid])->assertOk()
            ->assertSeeInOrder(['9876543210', "EMPLOYEES' PROVIDENT FUNDS SCHEME, 1952", 'Gopal Bhat', 'Rooplal Rao Bhat', '01-09-1994', 'PI Industry', 'GJ/VAD/12345/678', '101209880378', '01-07-2026', 'ABCDE1234F', '30012345678', 'State Bank of India', 'SBIN0001234'])
            ->assertSee('सदस्य का नाम');
        $this->form('pf-form-19', ['employee' => $this->employee->uuid, 'output' => 'print'])->assertSee('सदस्य का नाम')->assertSee('Personal Reasons');
        $this->form('pf-form-10c', ['employee' => $this->employee->uuid])->assertOk()
            ->assertSeeInOrder(['FORM 10C', "EMPLOYEES' PENSION SCHEME, 1995", 'GOPAL BHAT', '01/09/1994', 'Rooplal Rao Bhat', 'PI Industry', '01/07/2026', 'ROOPLAL RAO BHAT', '30012345678', 'STATE BANK OF INDIA']);

        foreach (['pf-act' => ['month' => '2026-09'], 'pf-form-10c' => [], 'pf-form-19' => []] as $form => $query) {
            $this->assertStringStartsWith('%PDF', $this->form($form, $query + ['output' => 'pdf'])->assertOk()->getContent(), $form);
        }
    }

    public function test_maternity_act_forms(): void
    {
        $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => 'maternity']))->assertOk()
            ->assertSeeInOrder(['Form K - Abstract of Maternity Act', 'Form L - Annual Return (Maternity)', 'Form M - Maternity Benefit Details', 'Form N - Payment Details for Maternity']);

        $woman = Employee::factory()->create(['company_id' => $this->company->id, 'gender' => 'female', 'employment_type' => 'full_time', 'date_of_joining' => '2025-01-01']);
        Employee::factory()->create(['company_id' => $this->company->id, 'gender' => 'female', 'employment_type' => 'intern', 'date_of_joining' => '2025-01-01']);
        foreach (range(0, 159) as $i) {
            $woman->attendances()->create(['company_id' => $this->company->id, 'punched_at' => CarbonImmutable::parse('2026-01-01 09:00')->addDays($i), 'work_date' => CarbonImmutable::parse('2026-01-01')->addDays($i)->toDateString(), 'type' => 'in']);
        }

        $this->form('maternity-form-l', ['year' => 2026])->assertOk()
            ->assertSeeInOrder(['Form L', 'MATERNITY BENEFIT ACT', '31st Dec 2026', 'PI Industry', 'permanently employed', '1', 'not less than 160 days', '1', 'Save']);

        $this->post(route('compliance.forms.values', [$this->company, 'maternity-form-k']), ['period' => '2026-12', 'raw_data' => ['opened_on' => '01-04-2010', 'lady_doctor' => 'No']])
            ->assertSessionHasNoErrors();
        $this->form('maternity-form-k', ['year' => 2026, 'output' => 'print'])->assertOk()
            ->assertSeeInOrder(['Form K', 'PI Industry', 'Active', '01-04-2010', 'Srympex', 'Is there a lady doctor?', 'No', 'Authorised Signatory'])->assertDontSee('Save');
        $this->form('maternity-form-k', ['year' => 2025])->assertDontSee('01-04-2010');

        foreach (['maternity-form-k', 'maternity-form-l', 'maternity-form-m', 'maternity-form-n'] as $form) {
            $this->form($form, ['year' => 2026])->assertOk();
            $this->assertStringStartsWith('%PDF', $this->form($form, ['year' => 2026, 'output' => 'pdf'])->assertOk()->getContent(), $form);
        }
        $this->form('maternity-form-n', ['year' => 2026])->assertSeeInOrder(['Form N', 'Under section 9A of the act', 'Remarks']);
    }

    public function test_police_verification_form(): void
    {
        $this->get(route('compliance.index', ['company' => $this->company->uuid, 'act' => 'verification']))->assertOk()->assertSee('Police verification');
        $this->employee->update(['phone' => '9876543210', 'aadhar_card_no' => '123412341234', 'driving_license_no' => 'GJ16 2020 1234567']);
        $this->employee->experiences()->create(['company_name' => 'Aarti Drugs', 'designation' => 'Fitter']);
        $other = Employee::factory()->create(['company_id' => $this->company->id]);

        $this->form('police-verification', ['employee' => $this->employee->uuid])->assertOk()
            ->assertSeeInOrder(['પોલીસ સ્ટેશન', 'Gopal Bhat', '32', '9876543210', 'ADHARCARD - 123412341234', 'GJ16 2020 1234567', 'Rooplal Rao Bhat (Father)', 'Fitter at Aarti Drugs', 'Srympex', 'PI Industry'])
            ->assertSee('Print')->assertDontSee('Download PDF')->assertSee('images/police-logo.png');
        $this->form('police-verification', ['employee' => $this->employee->uuid, 'output' => 'pdf'])->assertNotFound();

        $this->post(route('compliance.forms.values', [$this->company, 'police-verification']), [
            'period' => 'employee:'.$this->employee->id, 'raw_data' => ['station_name' => 'ઝઘડિયા', 'station_phone' => '02645-220033', 'vehicle_no' => 'GJ16 AB 1234'],
        ])->assertSessionHasNoErrors();

        $this->form('police-verification', ['employee' => $this->employee->uuid, 'output' => 'print'])->assertOk()
            ->assertSeeInOrder(['ઝઘડિયા', '02645-220033', 'GJ16 AB 1234'])->assertSee('window.print()', false);
        // The police station carries over to the company's other employees.
        $this->form('police-verification', ['employee' => $other->uuid, 'output' => 'print'])->assertSee('ઝઘડિયા')->assertSee('02645-220033')->assertDontSee('GJ16 AB 1234');
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
