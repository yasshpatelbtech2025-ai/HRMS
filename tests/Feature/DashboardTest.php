<?php

namespace Tests\Feature;

use App\Models\AttendanceRequest;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\SubDepartment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_counts_department_split_and_requests(): void
    {
        Carbon::setTestNow('2026-10-07 15:00:00'); // a Wednesday
        $this->actingAs(User::factory()->create());

        $company = Company::factory()->create();
        $shift = Shift::factory()->for($company)->create(['timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']]]);
        $department = Department::factory()->for($company)->create();
        $boiler = SubDepartment::factory()->for($department)->create(['company_id' => $company->id, 'name' => 'Boiler']);
        $make = fn (array $attrs = []) => Employee::factory()->create($attrs + [
            'company_id' => $company->id, 'shift_id' => $shift->id, 'sub_department_id' => $boiler->id,
            'date_of_joining' => '2026-01-01', 'weekly_leave_day' => 'sunday',
        ]);

        $present = $make(['first_name' => 'Present']);
        foreach (['in' => '09:00', 'out' => '14:30'] as $type => $time) {
            $present->attendances()->create(['company_id' => $company->id, 'punched_at' => "2026-10-07 {$time}", 'work_date' => '2026-10-07', 'type' => $type]);
        }
        $onLeave = $make(['first_name' => 'Leaving']);
        $type = LeaveType::create(['company_id' => $company->id, 'name' => 'Privilege', 'code' => 'PL', 'days' => 12, 'is_paid' => true]);
        LeaveApplication::create(['company_id' => $company->id, 'employee_id' => $onLeave->id, 'leave_type_id' => $type->id, 'start_date' => '2026-10-07', 'end_date' => '2026-10-07',
            'days' => 1, 'status' => 'approved', 'approved_start_date' => '2026-10-07', 'approved_end_date' => '2026-10-07', 'approved_days' => 1]);
        $make(['first_name' => 'Absent']);
        $make(['first_name' => 'Rest', 'weekly_leave_day' => 'wednesday']);
        $make(['first_name' => 'Gone'])->update(['separation_date' => '2026-09-30']);

        LeaveApplication::create(['company_id' => $company->id, 'employee_id' => $onLeave->id, 'leave_type_id' => $type->id, 'start_date' => '2026-10-20', 'end_date' => '2026-10-21', 'days' => 2]);
        AttendanceRequest::create(['company_id' => $company->id, 'employee_id' => $present->id, 'work_date' => '2026-10-06', 'requested_in' => '2026-10-06 09:00', 'requested_out' => '2026-10-06 18:00']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Total employee', '4', 'Today presents', '1', 'Today absents', '1', 'Today leave', '1'])
            ->assertSeeInOrder(['Daily attendance statistic (department wise)', 'Boiler', '1 present · 1 leave · 1 absent · 1 week off / holiday'])
            ->assertSeeInOrder(['Requests', 'Present', 'See All Attendance Requests', 'Leaving', 'PL', '2 days', 'See All Leave Requests']);
    }
}
