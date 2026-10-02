<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePunchTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');
        $this->actingAs(User::factory()->create());

        $this->company = Company::factory()->create();
        $shift = Shift::factory()->for($this->company)->rotational()->create();
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'shift_id' => $shift->id, 'date_of_joining' => '2026-09-03', 'weekly_leave_day' => 'sunday',
            'first_name' => 'Dashrath', 'last_name' => 'Thakor',
        ]);
    }

    private function punch(string $time, string $type): void
    {
        $this->post(route('attendance.raw-data.store'), ['employee' => $this->employee->uuid, 'time' => $time, 'type' => $type])->assertSessionHasNoErrors();
    }

    public function test_in_and_out_punches_pair_up_on_the_nearest_shift_part(): void
    {
        $this->punch('2026-09-10T06:05', 'in');
        $in = Attendance::sole();
        $this->assertTrue($in->is_missing, 'An in punch without an out is missing');
        $this->assertSame('FS', $in->shift_code);

        $this->punch('2026-09-10T14:02', 'out');
        $this->assertSame(0, Attendance::where('is_missing', true)->count());
        $this->assertSame(['FS', 'FS'], Attendance::orderBy('punched_at')->pluck('shift_code')->all());
        $this->assertSame('manual', $in->fresh()->source);
    }

    public function test_night_shift_out_next_morning_counts_for_the_evening_before(): void
    {
        $this->punch('2026-09-10T23:05', 'in');
        $this->punch('2026-09-11T06:10', 'out');

        $punches = Attendance::orderBy('punched_at')->get();
        $this->assertSame(['2026-09-10', '2026-09-10'], $punches->map->work_date->map->toDateString()->all());
        $this->assertSame(['NS', 'NS'], $punches->pluck('shift_code')->all());
        $this->assertTrue($punches->every->is_night_shift);
        $this->assertFalse($punches->contains->is_missing);
    }

    public function test_raw_data_page_lists_punches_for_the_month(): void
    {
        $this->punch('2026-09-10T06:05', 'in');
        $this->punch('2026-09-10T14:02', 'out');

        $this->get(route('attendance.raw-data', ['employee' => $this->employee->uuid, 'month' => '2026-09']))
            ->assertOk()
            ->assertSeeInOrder(['Employee attendance raw data', 'Add attendance data', 'Attendance data correction', 'Dashrath Thakor'])
            ->assertSeeInOrder(['Sl', 'Device', 'Time', 'Action', 'Type', 'Is night shift', 'Is missing', 'Original shift', 'Actual shift', 'Lock', 'Action'])
            ->assertSee('2026-09-10T14:02')->assertSee('MANUAL')->assertSee('Rotational Shift');

        $this->get(route('attendance.raw-data', ['employee' => $this->employee->uuid, 'month' => '2026-08']))->assertSee('No punches');
        $this->get(route('attendance.raw-data'))->assertOk()->assertSee('Select an employee');
    }

    public function test_punches_can_be_edited_deleted_and_locked(): void
    {
        $this->punch('2026-09-10T06:05', 'in');
        $this->punch('2026-09-10T14:02', 'out');
        [$in, $out] = Attendance::orderBy('punched_at')->get()->all();

        $this->patch(route('attendance.raw-data.update', $out), ['time' => '2026-09-10T15:00', 'type' => 'out'])->assertSessionHasNoErrors();
        $this->assertSame('15:00', $out->fresh()->punched_at->format('H:i'));

        $this->patch(route('attendance.raw-data.update', $out), ['time' => '2026-10-05T15:00', 'type' => 'out'])->assertSessionHasErrors('time');

        // Locking locks the whole day, and locked punches can't change.
        $this->patch(route('attendance.raw-data.lock', $in))->assertSessionHas('status');
        $this->assertTrue($out->fresh()->is_locked);
        $this->patch(route('attendance.raw-data.update', $out), ['time' => '2026-09-10T16:00', 'type' => 'out'])->assertSessionHasErrors('time');
        $this->delete(route('attendance.raw-data.destroy', $out))->assertSessionHasErrors('time');

        $this->patch(route('attendance.raw-data.lock', $in));
        $this->delete(route('attendance.raw-data.destroy', $out))->assertSessionHas('status');
        $this->assertModelMissing($out);
        $this->assertTrue($in->fresh()->is_missing, 'Deleting the out punch leaves the in punch unmatched');
    }

    public function test_correction_reworks_punches_after_a_shift_change(): void
    {
        $this->punch('2026-09-10T09:00', 'in');
        $this->assertSame('FS', Attendance::sole()->shift_code);

        $general = Shift::factory()->for($this->company)->create(['name' => 'General', 'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']]]);
        $this->employee->update(['shift_id' => $general->id]);

        $this->post(route('attendance.raw-data.correct'), ['employee' => $this->employee->uuid, 'from_date' => '2026-09-01', 'to_date' => '2026-09-30'])
            ->assertSessionHasNoErrors();
        $this->assertSame('GS', Attendance::sole()->shift_code);

        $this->post(route('attendance.raw-data.correct'), ['employee' => $this->employee->uuid, 'from_date' => '2026-09-30', 'to_date' => '2026-09-01'])
            ->assertSessionHasErrors('to_date');
    }

    public function test_export(): void
    {
        $this->punch('2026-09-10T06:05', 'in');

        $csv = $this->get(route('attendance.raw-data.export', ['format' => 'csv', 'employee' => $this->employee->uuid, 'month' => '2026-09']))->assertOk()->streamedContent();
        $this->assertStringContainsString('2026-09-10 06:05', $csv);
        $this->assertStringContainsString('In Time', $csv);
    }

    public function test_smart_attendance_fills_working_days_only(): void
    {
        $holiday = Holiday::create(['name' => 'Festival', 'start_date' => '2026-09-15', 'end_date' => '2026-09-15']);
        $this->company->holidays()->attach($holiday);
        $this->punch('2026-09-10T06:05', 'in');

        $this->get(route('attendance.smart'))->assertOk()->assertSeeInOrder(['Smart attendance', 'Company', 'Employee', 'Year', 'Month', 'Submit']);

        $this->post(route('attendance.smart.store'), ['company_id' => $this->company->id, 'employees' => [$this->employee->uuid], 'year' => 2026, 'month' => 9])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Attendance filled for 22 days across 1 employee(s).');

        // 30 days − 2 before joining − 4 Sundays − 1 holiday − 1 already punched = 22 days, two punches each.
        $smart = Attendance::where('source', 'smart')->get();
        $this->assertCount(44, $smart);
        $this->assertFalse($smart->contains->is_missing);
        $this->assertFalse($smart->contains(fn ($p) => $p->work_date->isSunday()));
        $this->assertSame('06:00', $smart->firstWhere('type', 'in')->punched_at->format('H:i'));

        // Running it again adds nothing.
        $this->post(route('attendance.smart.store'), ['company_id' => $this->company->id, 'employees' => [$this->employee->uuid], 'year' => 2026, 'month' => 9])
            ->assertSessionHas('status', 'Attendance filled for 0 days across 1 employee(s).');

        // Employees must belong to the chosen company.
        $this->post(route('attendance.smart.store'), ['company_id' => Company::factory()->create()->id, 'employees' => [$this->employee->uuid], 'year' => 2026, 'month' => 9])
            ->assertSessionHasErrors('employees.0');
    }
}
