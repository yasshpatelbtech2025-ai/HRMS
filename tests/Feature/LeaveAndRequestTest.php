<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\MonthlyAttendance;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveAndRequestTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Employee $employee;

    private LeaveType $pl;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
        $shift = Shift::factory()->for($this->company)->create(['name' => 'General', 'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']]]);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'shift_id' => $shift->id, 'date_of_joining' => '2026-01-01',
            'first_name' => 'Gopal', 'last_name' => 'Bhat', 'employee_code' => '0001', 'weekly_leave_day' => 'sunday',
        ]);
        $this->pl = LeaveType::create(['company_id' => $this->company->id, 'name' => 'Privilege Leave', 'code' => 'PL', 'days' => 12, 'is_paid' => true]);
    }

    private function apply(array $overrides = []): void
    {
        $this->post(route('attendance.leave.store'), $overrides + [
            'employee' => $this->employee->uuid, 'leave_type_id' => $this->pl->id, 'start_date' => '2026-09-21', 'end_date' => '2026-09-23',
        ])->assertSessionHasNoErrors();
    }

    public function test_leave_application_is_approved_for_the_managers_dates(): void
    {
        $this->apply(['hard_copy' => UploadedFile::fake()->create('letter.pdf', 50, 'application/pdf'), 'reason' => 'Family function']);
        $leave = LeaveApplication::sole();
        $this->assertSame('pending', $leave->status);
        $this->assertEquals(3, $leave->days, 'Days default to the date range');

        $this->get(route('attendance.leave'))
            ->assertOk()
            ->assertSeeInOrder(['Leaves', 'Leave type', 'Leave balance', 'Holiday list', 'Holiday', 'Leave list', 'Add leave application'])
            ->assertSeeInOrder(['Sl', 'Employee name', 'Type', 'Apply date', 'Leave start date', 'Leave end date', 'Days', 'Hard copy', 'Is paid', 'Status', 'Action'])
            ->assertSeeInOrder(['Gopal Bhat', 'PL', '2026-09-21', '2026-09-23', '3', 'View', 'Yes', 'Pending']);
        $this->get(route('attendance.leave.hard-copy', $leave))->assertOk();

        // Pending leave doesn't count on the grid.
        $grid = fn () => app(MonthlyAttendance::class)->forMonth(collect([$this->employee->fresh('shift')]), CarbonImmutable::create(2026, 9))[$this->employee->id]['days'];
        $this->assertSame('A', $grid()['2026-09-22']['status']);

        $this->put(route('attendance.leave.approve', $leave), ['approved_start_date' => '2026-09-22', 'approved_end_date' => '2026-09-23', 'approved_days' => 2, 'description' => 'One day less'])
            ->assertSessionHasNoErrors();
        $this->assertSame(['A', 'LEAVE', 'LEAVE'], [$grid()['2026-09-21']['status'], $grid()['2026-09-22']['status'], $grid()['2026-09-23']['status']]);

        // Overlapping approval is refused.
        $this->apply(['start_date' => '2026-09-23', 'end_date' => '2026-09-24']);
        $second = LeaveApplication::latest('id')->first();
        $this->put(route('attendance.leave.approve', $second), ['approved_start_date' => '2026-09-23', 'approved_end_date' => '2026-09-24'])
            ->assertSessionHasErrors('approved_start_date');

        $this->put(route('attendance.leave.reject', $second))->assertSessionHas('status');
        $this->assertSame('rejected', $second->fresh()->status);

        $csv = $this->get(route('attendance.leave.export', ['format' => 'csv', 'status' => 'approved']))->streamedContent();
        $this->assertStringContainsString('2026-09-22', $csv);
        $this->assertStringNotContainsString('Rejected', $csv);

        $this->delete(route('attendance.leave.destroy', $second))->assertSessionHas('status');
        $this->assertModelMissing($second);
    }

    public function test_leave_type_must_belong_to_the_employees_company(): void
    {
        $other = LeaveType::create(['company_id' => Company::factory()->create()->id, 'name' => 'Sick', 'code' => 'SL', 'days' => 6]);

        $this->post(route('attendance.leave.store'), ['employee' => $this->employee->uuid, 'leave_type_id' => $other->id, 'start_date' => '2026-09-21', 'end_date' => '2026-09-20'])
            ->assertSessionHasErrors(['leave_type_id', 'end_date']);
    }

    public function test_leave_balance_sheet(): void
    {
        $this->apply();
        $this->put(route('attendance.leave.approve', LeaveApplication::sole()), ['approved_start_date' => '2026-09-21', 'approved_end_date' => '2026-09-23']);

        $this->get(route('attendance.leave.balance', ['company' => $this->company->uuid, 'year' => 2026]))
            ->assertOk()
            ->assertSeeInOrder(['Leave Balance Sheet', 'Export Data', 'SL', 'Card No', 'Employee Name', 'PL', 'Total', 'Used', 'Balance'])
            ->assertSeeInOrder(['0001', 'Gopal Bhat', '12', '3', '9']);

        // Editing "used" records leave taken outside the system.
        $this->put(route('attendance.leave.balance.update', [$this->employee, $this->pl]), ['year' => 2026, 'total' => 15, 'used' => 5])->assertSessionHasNoErrors();
        $this->get(route('attendance.leave.balance', ['company' => $this->company->uuid, 'year' => 2026]))->assertSeeInOrder(['Gopal Bhat', '15', '5', '10']);

        $this->get(route('attendance.leave.balance.export', ['company' => $this->company->uuid, 'year' => 2026]))->assertOk()->assertDownload('leave-balance-2026.xlsx');
    }

    public function test_attendance_request_approval_sets_the_days_punches(): void
    {
        $this->employee->attendances()->create(['company_id' => $this->company->id, 'punched_at' => '2026-09-10 09:40', 'work_date' => '2026-09-10', 'type' => 'in']);

        $this->post(route('attendance.requests.store'), [
            'employee' => $this->employee->uuid, 'date' => '2026-09-10', 'requested_in' => '2026-09-10T09:00', 'requested_out' => '2026-09-10T18:00', 'reason' => 'Forgot to punch out',
        ])->assertSessionHasNoErrors();
        $request = AttendanceRequest::sole();

        $this->get(route('attendance.requests', ['company' => $this->company->uuid]))
            ->assertOk()
            ->assertSeeInOrder(['Attendance request', 'Employee id', 'Name', 'Designation', 'In time', 'Requested in time', 'Out time', 'Requested out time', 'Date', 'Original shift', 'Actual shift', 'Status'])
            ->assertSeeInOrder(['0001', 'Gopal Bhat', '09:40', '09:00', '--', '18:00', '2026-09-10', 'General', 'Pending', 'Forgot to punch out']);

        $this->put(route('attendance.requests.approve', $request))->assertSessionHas('status');
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame(['09:00', '18:00'], Attendance::orderBy('punched_at')->get()->map->punched_at->map->format('H:i')->all());
        $this->assertSame(['request', 'request'], Attendance::pluck('source')->all());

        $this->post(route('attendance.requests.store'), ['employee' => $this->employee->uuid, 'date' => '2026-09-11', 'requested_in' => '2026-09-11T18:00', 'requested_out' => '2026-09-11T09:00'])
            ->assertSessionHasErrors('requested_out');
    }
}
