<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BulkUploadTest extends TestCase
{
    use RefreshDatabase;

    private const COMPANY_HEADINGS = 'Establishment Name *,Connected company,Address Line 1,Address Line 2,Village,State,District,Taluka';

    private const ATTENDANCE_HEADINGS = 'Employee Code *,Date and Time * (YYYY-MM-DD HH:MM),Type * (IN/OUT)';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');
    }

    private function csv(string $name, string $headings, string ...$rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, implode("\n", [$headings, ...$rows]));
    }

    public function test_company_import_adds_every_row_and_links_connected_companies(): void
    {
        $this->actingAs(User::factory()->create());
        Company::factory()->create(['establishment_name' => 'Parent Works']);

        $this->get(route('company.import.template'))->assertOk()->assertDownload('companies-template.xlsx');
        $this->get(route('company.index'))->assertSee('Import Companies');

        $this->post(route('company.import'), ['file' => $this->csv('companies.csv', self::COMPANY_HEADINGS,
            'Alpha Textiles,parent works,12 Main Road,,Sachin,Gujarat,Surat,Choryasi',
            'Beta Dyeing,Alpha Textiles,,,,,,',
        )])->assertSessionHas('status', '2 companies imported.');

        $alpha = Company::where('establishment_name', 'Alpha Textiles')->sole();
        $this->assertSame('Parent Works', $alpha->parent->establishment_name);
        $this->assertSame('Surat', $alpha->district);
        $this->assertNull($alpha->address_line_2);
        $this->assertSame($alpha->id, Company::where('establishment_name', 'Beta Dyeing')->value('parent_company_id'));
    }

    public function test_company_import_saves_nothing_when_a_row_is_wrong(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('company.import'), ['file' => $this->csv('companies.csv', self::COMPANY_HEADINGS,
            'Good Company,,,,,,,',
            ',,,,,,,Taluka only',
            'Orphan Ltd,Nobody Ltd,,,,,,',
        )])->assertSessionHas('import_errors', [
            'Row 3: the establishment name is required.',
            'Row 4: the connected company "Nobody Ltd" does not exist.',
        ]);

        $this->assertSame(0, Company::count());
        $this->post(route('company.import'), ['file' => $this->csv('companies.csv', self::COMPANY_HEADINGS)])->assertSessionHasErrors('file');
        $this->post(route('company.import'), ['file' => UploadedFile::fake()->create('companies.pdf', 10, 'application/pdf')])->assertSessionHasErrors('file');
    }

    public function test_only_admins_import_companies(): void
    {
        $this->actingAs(User::factory()->user()->create());

        $this->post(route('company.import'), ['file' => $this->csv('companies.csv', self::COMPANY_HEADINGS, 'Sneaky Ltd,,,,,,,')])->assertForbidden();
        $this->assertSame(0, Company::count());
    }

    public function test_attendance_import_adds_punches_and_skips_repeats(): void
    {
        $this->actingAs(User::factory()->create());
        $company = Company::factory()->create();
        $shift = Shift::factory()->for($company)->rotational()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id, 'shift_id' => $shift->id, 'employee_code' => 'EMP0001', 'date_of_joining' => '2026-09-01']);

        $this->get(route('attendance.import.template'))->assertOk()->assertDownload('attendance-template.xlsx');
        $this->get(route('attendance.index'))->assertSee('Import Attendance');

        $file = fn () => $this->csv('punches.csv', self::ATTENDANCE_HEADINGS, 'emp0001,2026-10-01 09:00,IN', 'EMP0001,2026-10-01 18:00,out');

        $this->post(route('attendance.import'), ['company_id' => $company->id, 'file' => $file()])
            ->assertSessionHas('status', '2 punches imported (0 already existed and were skipped).');
        $this->assertSame(2, $employee->attendances()->where('source', 'import')->count());
        $this->assertSame([Attendance::IN, Attendance::OUT], $employee->attendances()->orderBy('punched_at')->pluck('type')->all());

        $this->post(route('attendance.import'), ['company_id' => $company->id, 'file' => $file()])
            ->assertSessionHas('status', '0 punches imported (2 already existed and were skipped).');
        $this->assertSame(2, $employee->attendances()->count());
    }

    public function test_attendance_import_saves_nothing_when_a_row_is_wrong(): void
    {
        $this->actingAs(User::factory()->create());
        $company = Company::factory()->create(['establishment_name' => 'Alpha Textiles']);
        $other = Company::factory()->create();
        $employee = Employee::factory()->create(['company_id' => $company->id, 'employee_code' => 'EMP0001']);
        Employee::factory()->create(['company_id' => $other->id, 'employee_code' => 'EMP0002']);

        $this->post(route('attendance.import'), ['company_id' => $company->id, 'file' => $this->csv('punches.csv', self::ATTENDANCE_HEADINGS,
            'EMP0001,2026-10-01 09:00,IN',
            'EMP0002,2026-10-01 09:00,IN',
            'EMP0001,yesterday-ish,IN',
            'EMP0001,2026-10-03 09:00,IN',
            'EMP0001,2026-10-01 18:00,BREAK',
        )])->assertSessionHas('import_errors', [
            'Row 3: no employee with code "EMP0002" in Alpha Textiles.',
            'Row 4: the date and time is not valid. Use YYYY-MM-DD HH:MM.',
            'Row 5: the date and time is in the future.',
            'Row 6: the type must be IN or OUT.',
        ]);

        $this->assertSame(0, $employee->attendances()->count());
    }

    public function test_attendance_import_is_limited_to_the_users_companies(): void
    {
        $mine = Company::factory()->create();
        $theirs = Company::factory()->create();
        Employee::factory()->create(['company_id' => $theirs->id, 'employee_code' => 'EMP0001']);
        $this->actingAs(User::factory()->user([$mine])->create());

        $this->post(route('attendance.import'), ['company_id' => $theirs->id, 'file' => $this->csv('punches.csv', self::ATTENDANCE_HEADINGS, 'EMP0001,2026-10-01 09:00,IN')])
            ->assertSessionHasErrors('company_id');
        $this->assertSame(0, Attendance::count());
    }
}
