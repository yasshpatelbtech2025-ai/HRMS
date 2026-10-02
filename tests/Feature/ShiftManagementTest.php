<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftRoster;
use App\Models\SubDepartment;
use App\Models\User;
use App\Services\Attendance\MonthlyAttendance;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ShiftManagementTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private SubDepartment $boiler;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 12:00:00');
        $this->actingAs(User::factory()->create());

        $this->company = Company::factory()->create();
        $department = Department::factory()->for($this->company)->create(['name' => 'Utilities']);
        $this->boiler = SubDepartment::factory()->for($department)->create(['company_id' => $this->company->id, 'name' => 'Boiler']);
        $shift = Shift::factory()->for($this->company)->rotational()->create();
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id, 'sub_department_id' => $this->boiler->id, 'shift_id' => $shift->id,
            'date_of_joining' => '2026-01-01', 'weekly_leave_day' => 'sunday', 'first_name' => 'Ronak', 'last_name' => 'Rajput', 'employee_code' => '0015',
        ]);
    }

    /**
     * September 2026 laid out as the browser would: weekly off Friday, night
     * shift every working day.
     */
    private function save(array $overrides = []): TestResponse
    {
        $days = [];
        for ($d = 1; $d <= 30; $d++) {
            $days[$d] = CarbonImmutable::create(2026, 9, $d)->isFriday() ? 'WO' : 'NS';
        }

        return $this->post(route('attendance.shifts.store', $this->company), ['year' => 2026, 'month' => 9, 'roster' => json_encode([array_replace_recursive([
            'id' => $this->boiler->id, 'parts' => ['NS'], 'iteration' => 6,
            'entries' => [['uuid' => $this->employee->uuid, 'weeklyOff' => 'friday', 'manual' => false, 'days' => $days]],
        ], $overrides)])]);
    }

    public function test_page_lists_departments_and_available_employees(): void
    {
        $this->get(route('attendance.shifts', ['company' => $this->company->uuid, 'year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertSeeInOrder(['Shift management', 'Department', 'Shift', 'Ittration', 'Employee', 'Week off', 'Edit', '1', 'Tue', '30', 'Wed', 'Save shifts'])
            ->assertSee('Boiler')
            ->assertSee('0015 - Ronak Rajput')
            ->assertSee('First Shift / Day Shift');
    }

    public function test_roster_is_saved_and_its_week_offs_replace_the_usual_one(): void
    {
        $this->save()->assertSessionHasNoErrors()->assertSessionHas('status', 'Shifts for September 2026 saved.');

        $roster = ShiftRoster::sole();
        $this->assertSame(['NS'], $roster->parts);
        $entry = $roster->entries()->sole();
        $this->assertSame('friday', $entry->weekly_off);
        $this->assertSame('WO', $entry->days['4']);
        $this->assertSame('NS', $entry->days['6']);

        $days = app(MonthlyAttendance::class)->forMonth(collect([$this->employee->fresh('shift')]), CarbonImmutable::create(2026, 9, 1))[$this->employee->id]['days'];
        $this->assertSame('WO', $days['2026-09-04']['status'], 'Rostered Friday off');
        $this->assertSame('A', $days['2026-09-06']['status'], 'Sunday is a working day on this roster');

        // Smart attendance punches at the rostered night shift times.
        $this->post(route('attendance.smart.store'), ['company_id' => $this->company->id, 'employees' => [$this->employee->uuid], 'year' => 2026, 'month' => 9]);
        $first = Attendance::orderBy('punched_at')->first();
        $this->assertSame('2026-09-01 23:00', $first->punched_at->format('Y-m-d H:i'));
        $this->assertSame('NS', $first->shift_code);
        $this->assertFalse(Attendance::whereDate('work_date', '2026-09-04')->exists());

        $this->get(route('attendance.shifts', ['company' => $this->company->uuid, 'year' => 2026, 'month' => 9]))->assertSee('\u0022weeklyOff\u0022:\u0022friday\u0022', false);
    }

    public function test_bad_codes_and_other_companies_are_rejected(): void
    {
        $this->save(['entries' => [['days' => [1 => 'XX']]]])->assertSessionHasErrors('roster.0.entries.0.days.1');
        $this->save(['iteration' => 3])->assertSessionHasErrors('roster.0.iteration');

        $other = Employee::factory()->create();
        $this->save(['entries' => [['uuid' => $other->uuid]]])->assertSessionHasErrors('roster.0.entries.0.uuid');

        $this->assertSame(0, ShiftRoster::count());
    }

    public function test_emptying_a_department_removes_its_roster(): void
    {
        $this->save();
        $this->assertSame(1, ShiftRoster::count());

        $this->post(route('attendance.shifts.store', $this->company), ['year' => 2026, 'month' => 9, 'roster' => json_encode([
            ['id' => $this->boiler->id, 'parts' => [], 'iteration' => 6, 'entries' => []],
        ])])->assertSessionHasNoErrors();

        $this->assertSame(0, ShiftRoster::count());
    }
}
