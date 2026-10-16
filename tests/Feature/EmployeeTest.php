<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeImportRun;
use App\Models\Position;
use App\Models\Shift;
use App\Models\SubDepartment;
use App\Models\User;
use App\Support\EmployeeImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private SubDepartment $subDepartment;

    private Position $position;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        $this->department = Department::factory()->for($this->company)->create(['name' => 'Utilities']);
        $this->subDepartment = SubDepartment::factory()->for($this->department)->create(['company_id' => $this->company->id, 'name' => 'Boiler']);
        $this->position = Position::factory()->for($this->company)->create(['name' => 'Operator']);
        $this->shift = Shift::factory()->for($this->company)->rotational()->create();
    }

    /**
     * A complete, valid Add employee submission.
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'first_name' => 'Firoz', 'middle_name' => 'Mohammed', 'last_name' => 'Shafi',
            'gender' => 'male', 'email' => 'firoz@example.com', 'phone' => '9516865130',
            'date_of_birth' => '2001-04-26', 'marital_status' => 'single', 'country' => 'India',
            'company_id' => $this->company->id, 'shift_id' => $this->shift->id, 'date_of_joining' => '2026-07-01',
            'works_on_hazardous_process' => '1', 'can_punch_in_hybrid_mode' => '0',
            'department_id' => $this->department->id, 'sub_department_id' => $this->subDepartment->id,
            'position_id' => $this->position->id, 'working_place' => 'near_running_machinery', 'employment_type' => 'full_time',
            'permanent_address_is_present' => '1',
            'addresses' => [
                'present' => ['address_line_1' => '12 Station Road', 'village' => 'Ankleshwar', 'district' => 'Bharuch', 'taluka' => 'Ankleshwar', 'state' => 'Gujarat'],
                'nominee_current' => ['village' => 'Valia'],
            ],
            'aadhar_card_no' => '1234 5678 9012', 'pan_card_no' => 'abcde1234f',
            'bank_name' => 'SBI', 'account_number' => '00112233445566', 'ifsc_code' => 'sbin0001234',
            'blood_group' => 'B+', 'is_disabled' => '0',
            'qualifications' => ['10th' => ['school' => 'GSEB School', 'passing_year' => '2017', 'percentage' => '72']],
            'job_status' => 'experience',
            'experiences' => [['company_name' => 'Acme Chem', 'designation' => 'Helper', 'total_years_worked' => '2']],
            'pay_frequency' => 'monthly', 'daily_hour' => '8', 'tax_regime' => 'new',
            'monthly_salary_calculation' => 'calendar_month', 'weekly_leave_day' => 'sunday',
            'salary' => ['basic' => ['monthly' => '15000', 'yearly' => '180000'], 'hra' => ['monthly' => '2000', 'yearly' => ''], 'bonus' => ['monthly' => '8.33']],
            'family' => ['mother' => ['full_name' => 'Amina Shafi', 'aadhar_card_no' => '999988887777', 'is_nominee' => '1']],
            'nfc_card_no' => 'NFC-001',
        ], $overrides);
    }

    public function test_guests_are_redirected(): void
    {
        auth()->logout();
        $this->get(route('employees.index'))->assertRedirect('/login');
        $this->get(route('employees.create'))->assertRedirect('/login');
    }

    public function test_create_form_renders_every_section(): void
    {
        $this->get(route('employees.create'))
            ->assertOk()
            ->assertSeeInOrder(['Basic Info', 'Employee Details', 'Address info', 'Employee Docs', 'Bank Info', 'Medical Info',
                'Qualification details', 'Experience details (in case of experience)', 'Salary info', 'Family Detail', 'Nfc card no']);
    }

    public function test_employee_is_created_with_every_section(): void
    {
        $this->post(route('employees.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('employees.index'));

        $employee = Employee::sole();
        $this->assertSame('0001', $employee->employee_code);
        $this->assertSame('Firoz Mohammed Shafi', $employee->full_name);
        $this->assertTrue($employee->shift->is($this->shift));
        $this->assertTrue($employee->works_on_hazardous_process);
        $this->assertSame('ABCDE1234F', $employee->pan_card_no);
        $this->assertSame('SBIN0001234', $employee->ifsc_code);
        $this->assertSame('123456789012', $employee->aadhar_card_no);

        // The permanent address follows the present one when the box is ticked.
        $this->assertSame('Bharuch', $employee->address('permanent')->district);
        $this->assertSame('Valia', $employee->address('nominee_current')->village);
        $this->assertNull($employee->address('nominee_permanent'));

        $this->assertSame('GSEB School', $employee->qualifications->sole()->school);
        $this->assertSame('Acme Chem', $employee->experiences->sole()->company_name);
        $this->assertTrue($employee->familyMembers->sole()->is_nominee);

        $salary = $employee->salaryElements->keyBy('element');
        $this->assertEquals(180000, $salary['basic']->yearly);
        $this->assertEquals(24000, $salary['hra']->yearly, 'Yearly defaults to 12 × monthly');
        $this->assertEquals(0, $salary['bonus']->yearly, 'Bonus is a % of basic, with no yearly amount');
    }

    public function test_identity_and_bank_numbers_are_encrypted_at_rest(): void
    {
        $this->post(route('employees.store'), $this->payload())->assertSessionHasNoErrors();

        $raw = DB::table('employees')->first();
        $this->assertStringNotContainsString('123456789012', $raw->aadhar_card_no);
        $this->assertStringNotContainsString('00112233445566', $raw->account_number);
        $this->assertStringNotContainsString('999988887777', DB::table('employee_family_members')->value('aadhar_card_no'));
    }

    public function test_employee_ids_are_numbered_per_company(): void
    {
        $this->post(route('employees.store'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('employees.store'), $this->payload(['email' => 'second@example.com']))->assertSessionHasNoErrors();

        $other = Company::factory()->create();
        $department = Department::factory()->for($other)->create();
        $this->post(route('employees.store'), $this->payload([
            'email' => 'third@example.com', 'company_id' => $other->id, 'department_id' => $department->id,
            'sub_department_id' => SubDepartment::factory()->for($department)->create(['company_id' => $other->id])->id,
            'position_id' => Position::factory()->for($other)->create()->id,
            'shift_id' => Shift::factory()->for($other)->create()->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(['0001', '0002'], Employee::where('company_id', $this->company->id)->orderBy('id')->pluck('employee_code')->all());
        $this->assertSame('0001', Employee::where('company_id', $other->id)->value('employee_code'));
    }

    public function test_required_fields_and_company_scoped_choices_are_validated(): void
    {
        $this->post(route('employees.store'), [])->assertSessionHasErrors([
            'first_name', 'last_name', 'gender', 'email', 'phone', 'date_of_birth', 'company_id', 'shift_id', 'date_of_joining',
            'department_id', 'sub_department_id', 'position_id', 'shift_id', 'working_place', 'pay_frequency', 'daily_hour',
            'monthly_salary_calculation', 'weekly_leave_day',
        ]);

        $otherDepartment = Department::factory()->create();
        $this->post(route('employees.store'), $this->payload([
            'department_id' => $otherDepartment->id,
            'position_id' => Position::factory()->create()->id,
            'shift_id' => Shift::factory()->create()->id,
            'pan_card_no' => 'NOT-A-PAN',
            'salary' => ['basic' => ['monthly' => '']],
        ]))->assertSessionHasErrors(['department_id', 'sub_department_id', 'position_id', 'shift_id', 'pan_card_no', 'salary.basic.monthly']);

        $this->post(route('employees.store'), $this->payload(['pay_frequency' => 'daily', 'salary' => ['basic' => ['monthly' => '']]]))
            ->assertSessionHasErrors('daily_rate')->assertSessionDoesntHaveErrors('salary.basic.monthly');
    }

    public function test_files_and_captured_photo_are_stored_and_served(): void
    {
        $photo = 'data:image/jpeg;base64,'.base64_encode(UploadedFile::fake()->image('p.jpg', 120, 150)->getContent());

        $this->post(route('employees.store'), $this->payload([
            'photo_data' => $photo,
            'aadhar_attachment' => UploadedFile::fake()->create('aadhar.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        $employee = Employee::sole();
        Storage::disk('local')->assertExists($employee->photo_path);
        Storage::disk('local')->assertExists($employee->aadhar_attachment_path);

        $this->get(route('employees.file', [$employee, 'photo']))->assertOk();
        $this->get(route('employees.file', [$employee, 'pan_attachment']))->assertNotFound();
        $this->get(route('employees.file', [$employee, 'secrets']))->assertNotFound();

        // Replacing the photo removes the old file.
        $old = $employee->photo_path;
        $this->put(route('employees.update', $employee), $this->payload(['photo' => UploadedFile::fake()->image('new.png')]))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($employee->fresh()->photo_path);
    }

    public function test_edit_form_shows_saved_values_and_update_saves_changes(): void
    {
        $this->post(route('employees.store'), $this->payload())->assertSessionHasNoErrors();
        $employee = Employee::sole();

        $this->get(route('employees.edit', $employee))->assertOk()->assertSee('Firoz')->assertSee('GSEB School')->assertSee('Amina Shafi');

        // Removing every experience row submits no experiences at all.
        $changes = $this->payload([
            'first_name' => 'Feroz', 'permanent_address_is_present' => '0',
            'addresses' => ['permanent' => ['district' => 'Surat']],
        ]);
        unset($changes['experiences']);
        $this->put(route('employees.update', $employee), $changes)->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertSame('Feroz', $employee->first_name);
        $this->assertSame('0001', $employee->employee_code, 'Editing keeps the employee id');
        $this->assertCount(0, $employee->experiences);
        $this->assertSame('Surat', $employee->address('permanent')->district);
    }

    public function test_list_shows_columns_filters_and_hides_separated_employees(): void
    {
        $shown = Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Ravi', 'last_name' => 'Thakor', 'employee_code' => '0007', 'blood_group' => 'O+', 'sub_department_id' => $this->subDepartment->id]);
        Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Mahesh', 'last_name' => 'Patel', 'blood_group' => 'A+']);
        Employee::factory()->separated()->create(['company_id' => $this->company->id, 'first_name' => 'Gone', 'last_name' => 'Away']);

        $this->get(route('employees.index'))
            ->assertOk()
            ->assertSeeInOrder(['Employee list', 'Filter', 'Import Employees', 'Add employee', 'Select Columns'])
            ->assertSeeInOrder(['Employee', 'Company', 'Contact', 'Department', 'Status', 'Action', 'Date of Birth', 'Designation', 'Joining Date', 'Shift'])
            ->assertSee('Ravi Thakor')->assertSee('Mahesh Patel')->assertDontSee('Gone Away');

        $this->get(route('employees.index', ['blood_group' => 'O+']))->assertSee('Ravi Thakor')->assertDontSee('Mahesh Patel');
        $this->get(route('employees.index', ['name' => 'ravi tha']))->assertSee('Ravi Thakor')->assertDontSee('Mahesh Patel');
        $this->get(route('employees.index', ['employee_code' => '0007']))->assertSee('Showing 1 to 1 of 1 entries');
        $this->get(route('employees.index', ['sub_department_id' => $this->subDepartment->id]))->assertSee('Ravi Thakor')->assertDontSee('Mahesh Patel');
        $this->get(route('employees.index', ['per_page' => 'all']))->assertSee('Showing 1 to 2 of 2 entries');
        $this->assertTrue($shown->exists);
    }

    public function test_select_columns_is_remembered_per_user(): void
    {
        Employee::factory()->create(['company_id' => $this->company->id, 'email' => 'visible@example.com']);

        $this->get(route('employees.index'))->assertDontSee('visible@example.com');

        $this->post(route('employees.columns'), ['column' => 'email'])->assertRedirect();
        $this->get(route('employees.index'))->assertSee('visible@example.com');

        $this->post(route('employees.columns'), ['column' => 'email']);
        $this->get(route('employees.index'))->assertDontSee('visible@example.com');

        $this->post(route('employees.columns'), ['column' => 'salary'])->assertSessionHasErrors('column');
    }

    public function test_status_toggle_and_delete(): void
    {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $this->patch(route('employees.status', $employee))->assertSessionHas('status');
        $this->assertSame(EmployeeStatus::Inactive, $employee->fresh()->status);
        $this->patch(route('employees.status', $employee));
        $this->assertSame(EmployeeStatus::Active, $employee->fresh()->status);

        $report = Employee::factory()->create(['company_id' => $this->company->id, 'reporting_officer_id' => $employee->id]);
        $this->delete(route('employees.destroy', $employee))->assertSessionHas('error');
        $this->assertNotSoftDeleted($employee);

        $report->delete();
        $this->delete(route('employees.destroy', $employee))->assertSessionHas('status');
        $this->assertSoftDeleted($employee);
    }

    public function test_separation_moves_employee_to_the_separation_list(): void
    {
        $employee = Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Kishan', 'last_name' => 'Rao', 'date_of_joining' => '2025-01-01']);

        $this->put(route('employees.separation', $employee), ['separation_date' => '2024-12-31'])->assertSessionHasErrors('separation_date');

        $this->put(route('employees.separation', $employee), ['separation_date' => today()->toDateString()])->assertSessionHas('status');
        $this->get(route('employees.index'))->assertSee('Showing 0 entries');
        $this->get(route('employees.separations'))->assertOk()->assertSee('Kishan Rao');

        $this->put(route('employees.separation', $employee), ['separation_date' => ''])->assertSessionHas('status');
        $this->assertNull($employee->fresh()->separation_date);
        $this->get(route('employees.index'))->assertSee('Kishan Rao');
        $this->get(route('employees.separations'))->assertSee('No separated employees.');
    }

    public function test_details_sheet_masks_sensitive_numbers(): void
    {
        $this->post(route('employees.store'), $this->payload())->assertSessionHasNoErrors();
        $employee = Employee::sole();

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSeeInOrder(['EMPLOYEE DETAILS FORM', '1. Basic Information', '2. Employment Information', '3. Salary Information', '4. Present Address', '11. Family Details'])
            ->assertSee('Firoz Mohammed Shafi')
            ->assertSee('9012')->assertDontSee('123456789012')
            ->assertDontSee('00112233445566');
    }

    public function test_export_and_letters(): void
    {
        Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Ravi', 'last_name' => 'Thakor']);

        $csv = $this->get(route('employees.export', ['format' => 'csv']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Name of employee', $csv);
        $this->assertStringContainsString('Ravi Thakor', $csv);
        $this->get(route('employees.export', ['format' => 'xlsx']))->assertOk();

        $employee = Employee::sole();
        $this->get(route('employees.letter', [$employee, 'offer']))->assertOk()->assertSee('Offer Letter');
        $this->get(route('employees.letter', [$employee, 'resignation']))->assertNotFound();
    }

    public function test_id_card_list_print_and_assign(): void
    {
        $employee = Employee::factory()->create(['company_id' => $this->company->id, 'first_name' => 'Dashrath', 'last_name' => 'Thakor', 'position_id' => $this->position->id]);

        $this->get(route('employees.id-cards'))
            ->assertOk()
            ->assertSeeInOrder(['Employee list for id card', 'Sl', 'Employee id', 'Employee name', 'Position', 'Contact no', 'Assign date', 'Action'])
            ->assertSeeInOrder(['Dashrath Thakor', 'Operator', 'Not Assigned']);

        $this->get(route('employees.id-cards.show', $employee))->assertOk()->assertSee('PI Industry')->assertSee('Mark as assigned today');

        $this->post(route('employees.id-cards.assign', $employee))->assertSessionHas('status');
        $this->assertTrue($employee->fresh()->id_card_assigned_on->isToday());
        $this->get(route('employees.id-cards'))->assertDontSee('Not Assigned');
    }

    public function test_import_template_and_all_or_nothing_import(): void
    {
        $this->get(route('employees.import.template', $this->company))->assertOk()->assertDownload();

        $headings = array_keys(EmployeeImport::COLUMNS);
        $row = fn (array $values) => implode(',', array_map(fn ($h) => $values[$h] ?? '', $headings));
        $good = [
            'First Name *' => 'Ravi', 'Last Name *' => 'Thakor', 'Gender *' => 'Male', 'Email *' => 'ravi@example.com', 'Phone *' => '9000000001',
            'Date of Birth * (YYYY-MM-DD)' => '1999-01-01', 'Shift *' => 'rotational shift', 'Joining Date * (YYYY-MM-DD)' => '2026-01-01',
            'Working on hazardous process * (Yes/No)' => 'No', 'Can Punch In Hybrid Mode * (Yes/No)' => 'Yes',
            'Department *' => 'utilities', 'Sub Department *' => 'Boiler', 'Designation *' => 'Operator',
            'Working Place *' => 'Worker operates automated equipment only', 'Employee Type *' => 'Full Time',
            'Pay frequency * (Monthly/Daily/Hourly)' => 'Monthly', 'Basic (monthly)' => '14000', 'Daily hour *' => '8',
            'Monthly salary calculation type *' => 'EVERY MONTH 30 DAYS', 'Weekly leave day *' => 'Sunday',
        ];
        $csv = fn (array ...$rows) => UploadedFile::fake()->createWithContent('employees.csv', implode("\n", [implode(',', $headings), ...array_map($row, $rows)]));

        // Imports run as a background job (synchronously in tests). One bad
        // row (unknown designation) means nothing is imported.
        $this->post(route('employees.import'), ['company_id' => $this->company->id, 'file' => $csv($good, ['Email *' => 'x@example.com', 'Designation *' => 'Astronaut'] + $good)])
            ->assertSessionHas('status', 'Import started. Its progress is shown at the top of the list.');
        $this->assertSame(0, Employee::count());
        $run = EmployeeImportRun::latest('id')->first();
        $this->assertSame('invalid', $run->status);
        $this->assertCount(1, $run->errors);
        $this->assertStringStartsWith('Row 3:', $run->errors[0]);
        Storage::disk('local')->assertMissing($run->path);
        $this->get(route('employees.index'))->assertSee('Nothing was imported from employees.csv')->assertSee('Row 3:');
        $this->getJson(route('employees.import.status', $run))->assertJson(['status' => 'invalid', 'finished' => true]);

        $this->post(route('employees.import'), ['company_id' => $this->company->id, 'file' => $csv($good, ['Email *' => 'mahesh@example.com', 'First Name *' => 'Mahesh'] + $good)]);
        $this->assertSame(['status' => 'done', 'imported' => 2], EmployeeImportRun::latest('id')->first()->only('status', 'imported'));
        $this->get(route('employees.index'))->assertSee('Import finished: employees.csv.');

        $this->assertSame(['0001', '0002'], Employee::orderBy('id')->pluck('employee_code')->all());
        $ravi = Employee::where('email', 'ravi@example.com')->sole();
        $this->assertSame('thirty_days', $ravi->monthly_salary_calculation);
        $this->assertTrue($ravi->can_punch_in_hybrid_mode);
        $this->assertEquals(14000, $ravi->salaryElements->sole()->monthly);
    }
}
