<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\CompOffUse;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\MonthlyAttendance;
use App\Services\Attendance\PunchProcessor;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');
        $this->actingAs(User::factory()->create());

        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        $shift = Shift::factory()->for($this->company)->create([
            'name' => 'General', 'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']],
            'ot_after_hours' => 9, 'late_penalty_after_minutes' => 15,
        ]);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'shift_id' => $shift->id, 'date_of_joining' => '2026-09-01',
            'weekly_leave_day' => 'sunday', 'first_name' => 'Dashrath', 'last_name' => 'Thakor', 'employee_code' => '0001',
        ]);
        $this->leaveType = LeaveType::create(['company_id' => $this->company->id, 'name' => 'Privilege Leave', 'code' => 'PL', 'days' => 12, 'is_paid' => true]);
        $this->company->holidays()->attach(Holiday::create(['name' => 'Festival', 'start_date' => '2026-09-15', 'end_date' => '2026-09-15']));
    }

    private function work(string $date, ?string $in, ?string $out = null): void
    {
        foreach (['in' => $in, 'out' => $out] as $type => $time) {
            if ($time) {
                $this->employee->attendances()->create([
                    'company_id' => $this->company->id, 'punched_at' => "{$date} {$time}", 'work_date' => $date, 'type' => $type,
                ]);
            }
        }
        app(PunchProcessor::class)->process($this->employee->fresh(), CarbonImmutable::parse($date), CarbonImmutable::parse($date));
    }

    private function september(): array
    {
        return app(MonthlyAttendance::class)->forMonth(collect([$this->employee->fresh('shift')]), CarbonImmutable::create(2026, 9, 1))[$this->employee->id];
    }

    public function test_day_statuses_follow_punches_rules_holidays_and_leave(): void
    {
        $this->work('2026-09-01', '09:00', '18:00'); // full day
        $this->work('2026-09-02', '09:30', '18:00'); // 30 min late → half day penalty
        $this->work('2026-09-03', '09:00', '13:00'); // 4 hours → half day
        $this->work('2026-09-04', '09:00', '10:00'); // 1 hour → unpaid
        $this->work('2026-09-05', '09:00');          // missing out punch
        $this->work('2026-09-09', '09:00', '20:00'); // 11 hours → 2 hours overtime
        $this->work('2026-09-13', '09:00', '18:00'); // worked on Sunday → earns a COFF
        LeaveApplication::create([
            'company_id' => $this->company->id, 'employee_id' => $this->employee->id, 'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-09-07', 'end_date' => '2026-09-07', 'days' => 1, 'status' => 'approved',
            'approved_start_date' => '2026-09-07', 'approved_end_date' => '2026-09-07', 'approved_days' => 1,
        ]);
        CompOffUse::create(['employee_id' => $this->employee->id, 'work_date' => '2026-09-08']);

        $days = $this->september()['days'];
        $status = fn ($day) => $days[sprintf('2026-09-%02d', $day)]['status'];

        $this->assertSame('P', $status(1));
        $this->assertEquals(9, $days['2026-09-01']['hours']);
        $this->assertSame('HD', $status(2));
        $this->assertTrue($days['2026-09-02']['late']);
        $this->assertSame('HD', $status(3));
        $this->assertSame('A', $status(4));
        $this->assertSame('A', $status(5));
        $this->assertTrue($days['2026-09-05']['missing']);
        $this->assertSame('WO', $status(6));
        $this->assertSame('LEAVE', $status(7));
        $this->assertSame('COFF', $status(8));
        $this->assertSame('P', $status(9));
        $this->assertEquals(2, $days['2026-09-09']['ot']);
        $this->assertSame('P', $status(13));
        $this->assertTrue($days['2026-09-13']['offDayWork']);
        $this->assertSame('PH', $status(15));
        $this->assertSame('A', $status(16));

        $totals = $this->september()['totals'];
        // 30 days: P 1,9,13 · HD 2,3 · LEAVE 7 · COFF 8 · PH 15 · WO 6,20,27 · LOP the other 18.
        $this->assertSame([3.0, 2.0, 1.0, 1.0, 1.0, 3.0, 19.0], [$totals['P'], $totals['HD'], $totals['LEAVE'], $totals['COFF'], $totals['PH'], $totals['WO'], $totals['A']]);

        $october = app(MonthlyAttendance::class)->forMonth(collect([$this->employee->fresh('shift')]), CarbonImmutable::create(2026, 10, 1))[$this->employee->id]['days'];
        $this->assertNull($october['2026-10-03']['status'], 'Future days have no status');
    }

    public function test_compensatory_off_balance(): void
    {
        $attendance = app(MonthlyAttendance::class);
        $this->assertSame(0, $attendance->compOffBalance($this->employee->fresh('shift'), 2026));

        $this->work('2026-09-13', '09:00', '18:00'); // Sunday
        $this->work('2026-09-15', '09:00', '18:00'); // holiday
        $this->assertSame(2, $attendance->compOffBalance($this->employee->fresh('shift'), 2026));

        CompOffUse::create(['employee_id' => $this->employee->id, 'work_date' => '2026-09-16']);
        $this->assertSame(1, $attendance->compOffBalance($this->employee->fresh('shift'), 2026));

        // Shifts that pay off-day work as overtime earn no COFF.
        $this->employee->shift->update(['wo_ph_work_pay' => 'ot']);
        $this->assertSame(0, $attendance->compOffBalance($this->employee->fresh('shift'), 2026));
    }

    public function test_grid_page(): void
    {
        $this->work('2026-09-01', '09:00', '18:00');

        $this->get(route('attendance.index', ['company' => $this->company->uuid, 'year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertSeeInOrder(['Monthly attendance', 'Missing punch', 'Attendance requests', 'Shift management', 'Attendance raw data', 'Smart attendance', 'Leave'])
            ->assertSeeInOrder(['Monthly present report', 'Employee', '01', 'Tue', '30', 'Wed', 'P', 'LOP', 'PH', 'WO', 'HD', 'LEAVE', 'COFF'])
            ->assertSee('Dashrath Thakor')
            ->assertSee('Enter In-Time &amp; Out-Time', false)
            ->assertSee('Enter Leave');

        $this->get(route('attendance.index'))->assertOk();
    }

    public function test_grid_actions_set_time_leave_coff_and_reset(): void
    {
        $this->post(route('attendance.monthly.time'), ['employee' => $this->employee->uuid, 'date' => '2026-09-10', 'in_time' => '2026-09-10T09:00', 'out_time' => '2026-09-10T18:00'])
            ->assertSessionHasNoErrors();
        $this->assertSame('P', $this->september()['days']['2026-09-10']['status']);

        // Setting times again replaces the day's punches.
        $this->post(route('attendance.monthly.time'), ['employee' => $this->employee->uuid, 'date' => '2026-09-10', 'in_time' => '2026-09-10T09:00', 'out_time' => '2026-09-10T12:00']);
        $this->assertSame(2, Attendance::count());
        $this->assertSame('HD', $this->september()['days']['2026-09-10']['status']);

        $this->post(route('attendance.monthly.time'), ['employee' => $this->employee->uuid, 'date' => '2026-09-11', 'in_time' => '2026-09-11T18:00', 'out_time' => '2026-09-11T09:00'])
            ->assertSessionHasErrors('out_time');

        $this->post(route('attendance.monthly.leave'), ['employee' => $this->employee->uuid, 'leave_type_id' => $this->leaveType->id, 'start_date' => '2026-09-21', 'end_date' => '2026-09-22', 'days' => 2])
            ->assertSessionHasNoErrors();
        $this->assertSame(['LEAVE', 'LEAVE'], [$this->september()['days']['2026-09-21']['status'], $this->september()['days']['2026-09-22']['status']]);
        $this->post(route('attendance.monthly.leave'), ['employee' => $this->employee->uuid, 'leave_type_id' => $this->leaveType->id, 'start_date' => '2026-09-22', 'end_date' => '2026-09-22', 'days' => 1])
            ->assertSessionHasErrors('start_date');

        // A day inside a multi-day leave can't be reset from the grid.
        $this->post(route('attendance.monthly.reset'), ['employee' => $this->employee->uuid, 'date' => '2026-09-21'])->assertSessionHas('error');

        // COFF needs a balance.
        $this->post(route('attendance.monthly.coff'), ['employee' => $this->employee->uuid, 'date' => '2026-09-23'])->assertSessionHas('error');
        $this->work('2026-09-13', '09:00', '18:00');
        $this->post(route('attendance.monthly.coff'), ['employee' => $this->employee->uuid, 'date' => '2026-09-23'])->assertSessionHas('status');
        $this->assertSame('COFF', $this->september()['days']['2026-09-23']['status']);

        $this->post(route('attendance.monthly.reset'), ['employee' => $this->employee->uuid, 'date' => '2026-09-23'])->assertSessionHas('status');
        $this->post(route('attendance.monthly.reset'), ['employee' => $this->employee->uuid, 'date' => '2026-09-10'])->assertSessionHas('status');
        $this->assertSame(['A', 'A'], [$this->september()['days']['2026-09-23']['status'], $this->september()['days']['2026-09-10']['status']]);

        // Locked days can't be changed from the grid.
        $this->work('2026-09-24', '09:00', '18:00');
        Attendance::whereDate('work_date', '2026-09-24')->update(['is_locked' => true]);
        $this->post(route('attendance.monthly.reset'), ['employee' => $this->employee->uuid, 'date' => '2026-09-24'])->assertSessionHasErrors('date');
    }

    public function test_missing_punch_and_employee_report(): void
    {
        $this->work('2026-09-05', '09:00');
        $this->work('2026-09-09', '09:00', '20:00');

        $this->get(route('attendance.missing-punch', ['company' => $this->company->uuid, 'month' => '2026-09']))
            ->assertOk()
            ->assertSeeInOrder(['Missing attendance', 'Attendance form', 'Employee id', 'Name', 'Designation', 'In time', 'Out time', 'Date', 'Original shift', 'Actual shift'])
            ->assertSeeInOrder(['0001', 'Dashrath Thakor', '09:00', '2026-09-05', 'General', 'GS']);

        $this->get(route('attendance.employee-report', ['employee' => $this->employee->uuid, 'month' => '2026-09']))
            ->assertOk()
            ->assertSeeInOrder(['Employee wise attendance report', 'Dashrath Thakor', 'Attendance History ( From 2026-09-01 To 2026-09-30 )'])
            ->assertSeeInOrder(['Sl', 'Date', 'Day', 'In time', 'Out time', 'Total worked hour', 'Total over time', 'Attendance status', 'Original shift', 'Actual shift', 'Lock'])
            ->assertSeeInOrder(['2026-09-05', 'Saturday', '09:00', 'MP'])
            ->assertSeeInOrder(['2026-09-09', 'Wednesday', '09:00', '20:00', '11:00', '2:00', 'P']);
    }
}
