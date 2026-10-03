<?php

namespace Tests\Feature;

use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\PunchProcessor;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-07 20:00:00');
        $this->actingAs(User::factory()->create());

        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        AttendanceSetting::create(['company_id' => $this->company->id, 'punch_buffer_minutes' => 10]);
        $shift = Shift::factory()->for($this->company)->create(['name' => 'General', 'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']]]);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'shift_id' => $shift->id, 'date_of_joining' => '2026-01-01', 'weekly_leave_day' => 'sunday',
            'first_name' => 'Gopal', 'last_name' => 'Bhat', 'employee_code' => '0001',
        ]);

        foreach (['2026-10-05' => ['09:05', '18:00'], '2026-10-06' => ['09:40', '17:30'], '2026-10-07' => ['09:00', '18:00']] as $date => [$in, $out]) {
            foreach (['in' => $in, 'out' => $out] as $type => $time) {
                $this->employee->attendances()->create(['company_id' => $this->company->id, 'punched_at' => "{$date} {$time}", 'work_date' => $date, 'type' => $type]);
            }
        }
        app(PunchProcessor::class)->process($this->employee->fresh('shift'), CarbonImmutable::parse('2026-10-05'), CarbonImmutable::parse('2026-10-07'));
    }

    public function test_attendance_sheet_shows_in_and_out_times_by_day(): void
    {
        $this->get(route('reports.index', ['company' => $this->company->uuid, 'month' => '2026-10']))
            ->assertOk()
            ->assertSeeInOrder(['Attendance sheet', 'Attendance report', 'Lateness closing attendance report', 'Attendance log', 'Daily present', 'Employee report'])
            ->assertSeeInOrder(['Monthly attendance sheet', 'CSV', 'Excel', 'PDF', 'Print', 'ID', 'Employee Name', 'Time', 'Attendance', '01', '31'])
            ->assertSeeInOrder(['0001', 'Gopal Bhat', 'In Time', 'WO', '09:05', '09:40', '09:00', 'Out Time', '18:00', '17:30', '18:00']);

        $csv = $this->get(route('reports.index', ['company' => $this->company->uuid, 'month' => '2026-10', 'format' => 'csv']))->streamedContent();
        $this->assertStringContainsString('In Time', $csv);
        $this->assertStringContainsString('09:40', $csv);
    }

    public function test_attendance_summary(): void
    {
        $this->get(route('reports.attendance', ['company' => $this->company->uuid, 'from' => '2026-10-01', 'to' => '2026-10-07']))
            ->assertOk()
            ->assertSeeInOrder(['Attendance report', 'PI Industry', '2026-10-01 – 2026-10-07'])
            ->assertSeeInOrder(['Gopal Bhat', '2', '1', '3', '1']); // P (5th, 7th), HD (6th: late), LOP (1st–3rd), WO (4th)
    }

    public function test_lateness_report(): void
    {
        $this->get(route('reports.lateness', ['company' => $this->company->uuid, 'month' => '2026-10']))
            ->assertOk()
            ->assertSee('10-minute punch buffer')
            ->assertSeeInOrder(['2026-10-06', 'GS', '09:00–18:00', '09:40', '17:30', '40', '30'])
            ->assertDontSee('2026-10-05', false); // 5 minutes late is within the buffer
    }

    public function test_attendance_log_daily_present_and_employee_report(): void
    {
        $this->get(route('reports.attendance-log', ['company' => $this->company->uuid, 'from' => '2026-10-06', 'to' => '2026-10-06']))
            ->assertOk()->assertSeeInOrder(['Gopal Bhat', '2026-10-06 09:40', 'In', 'MANUAL', 'General', 'GS', '2026-10-06 17:30', 'Out']);

        $this->get(route('reports.daily-present', ['company' => $this->company->uuid, 'date' => '2026-10-07']))
            ->assertOk()->assertSee('1 of 1 employees present.')->assertSeeInOrder(['Gopal Bhat', '09:00', '18:00', '9', 'Present']);
        $this->get(route('reports.daily-present', ['company' => $this->company->uuid, 'date' => '2026-10-04']))->assertSee('0 of 1 employees present.');

        $this->get(route('reports.employees', ['company' => $this->company->uuid]))
            ->assertOk()->assertSeeInOrder(['Employee report', 'Gopal Bhat', '2026-01-01', 'General', 'Full Time', 'Active']);

        foreach (['csv', 'xlsx', 'pdf'] as $format) {
            $this->get(route('reports.employees', ['company' => $this->company->uuid, 'format' => $format]))->assertOk()->assertDownload();
        }
        $this->get(route('reports.employees', ['format' => 'docx']))->assertNotFound();
    }
}
