<?php

namespace Tests\Feature;

use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceFoundationsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->company = Company::factory()->create(['establishment_name' => 'PI Industry']);
    }

    private function rotational(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Rotational Shift',
            'timings' => ['FS' => ['start' => '06:00', 'end' => '14:00'], 'SS' => ['start' => '14:00', 'end' => '23:00'], 'NS' => ['start' => '23:00', 'end' => '06:00']],
            'half_day_rule' => 'slab', 'wo_ph_work_pay' => 'ot', 'pay_daily_rate_on_wo_ph' => '1', 'ot_effect_on' => 'gross', 'ot_rate' => 'double',
            'ot_after_hours' => '8', 'late_penalty_after_minutes' => '15', 'pf_effect_on' => 'basic', 'esi_effect_on' => 'gross',
        ], $overrides);
    }

    public function test_settings_page_shows_every_shift_type(): void
    {
        $this->get(route('configs.index'))
            ->assertOk()
            ->assertSeeInOrder(['Attendance', 'Payroll', 'Attendance setting', 'PI Industry', 'Employee Punch Buffer Time (In Minutes)',
                'Open Shift', 'General Shift', 'Day &amp; Night Shift (12 Hour Rotational)', 'Rotational Shift'], false)
            ->assertSee('How do you want to Manage Employee Half Day ?')
            ->assertSee('Do you want to deduct penalty for Employee late coming ? (Half Day Penalty)');
    }

    public function test_shifts_and_their_rules_are_saved(): void
    {
        $this->put(route('configs.attendance.update', $this->company), [
            'punch_buffer_minutes' => 20,
            'shifts' => [
                'general' => [1 => ['name' => 'General Shift', 'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']]]],
                'rotational' => [1 => $this->rotational()],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect(route('configs.index', ['company' => $this->company->uuid]));

        $this->assertSame(20, AttendanceSetting::where('company_id', $this->company->id)->value('punch_buffer_minutes'));
        $this->assertSame(['General Shift', 'Rotational Shift'], $this->company->shifts()->orderBy('type')->pluck('name')->all());

        $rotational = Shift::where('type', 'rotational')->sole();
        $this->assertSame(['FS', 'SS', 'NS'], array_keys($rotational->timings));
        $this->assertSame('23:00', $rotational->timings['NS']['start']);
        $this->assertSame('ot', $rotational->wo_ph_work_pay);
        $this->assertTrue($rotational->pay_daily_rate_on_wo_ph);
        $this->assertSame('double', $rotational->ot_rate);
        $this->assertSame(15, $rotational->late_penalty_after_minutes);

        $general = Shift::where('type', 'general')->sole();
        $this->assertSame('slab', $general->half_day_rule, 'Unsent rules keep their defaults');
        $this->assertSame('gross', $general->esi_effect_on);

        $this->get(route('configs.index', ['company' => $this->company->uuid]))->assertSee('value="Rotational Shift"', false)->assertSee('value="23:00"', false);
    }

    public function test_titled_shifts_need_timings_and_unique_names(): void
    {
        $this->put(route('configs.attendance.update', $this->company), ['shifts' => [
            'general' => [1 => ['name' => 'Day', 'timings' => ['GS' => ['start' => '09:00']]], 2 => ['name' => 'day', 'timings' => ['GS' => ['start' => '10:00', 'end' => '19:00']]]],
            'rotational' => [1 => $this->rotational(['ot_rate' => 'triple'])],
        ]])->assertSessionHasErrors(['shifts.general.1.timings.GS.end', 'shifts.general.2.name', 'shifts.rotational.1.ot_rate']);

        $this->assertSame(0, Shift::count());
    }

    public function test_clearing_a_title_removes_the_shift_unless_employees_use_it(): void
    {
        $shift = Shift::factory()->for($this->company)->create(['name' => 'General Shift', 'slot' => 1]);
        $employee = Employee::factory()->create(['company_id' => $this->company->id, 'shift_id' => $shift->id]);

        $this->put(route('configs.attendance.update', $this->company), ['shifts' => ['general' => [1 => ['name' => '']]]])
            ->assertSessionHasErrors('shifts.general.1.name');
        $this->assertModelExists($shift);

        $employee->update(['shift_id' => null]);
        $this->put(route('configs.attendance.update', $this->company), ['shifts' => ['general' => [1 => ['name' => '']]]])->assertSessionHasNoErrors();
        $this->assertModelMissing($shift);
    }

    public function test_leave_types_are_managed_per_company(): void
    {
        $this->get(route('attendance.leave.types'))
            ->assertOk()
            ->assertSeeInOrder(['Leaves', 'Leave type', 'Leave balance', 'Holiday list', 'Holiday', 'Leave type list', 'Add leave type']);

        $this->post(route('attendance.leave.types.store'), ['company_id' => $this->company->id, 'name' => 'Privilege Leave', 'code' => 'pl', 'days' => 12, 'is_paid' => 1])
            ->assertSessionHasNoErrors();
        $type = LeaveType::sole();
        $this->assertSame('PL', $type->code);
        $this->assertTrue($type->is_paid);

        $this->post(route('attendance.leave.types.store'), ['company_id' => $this->company->id, 'name' => 'Paid Leave', 'code' => 'PL', 'days' => 5])
            ->assertSessionHasErrors('code');
        $this->post(route('attendance.leave.types.store'), ['company_id' => Company::factory()->create()->id, 'name' => 'Privilege Leave', 'code' => 'PL', 'days' => 10])
            ->assertSessionHasNoErrors();

        $this->get(route('attendance.leave.types'))->assertSeeInOrder(['PI Industry', 'Privilege Leave', '(PL)', '12', 'Yes']);

        $this->put(route('attendance.leave.types.update', $type), ['company_id' => $this->company->id, 'name' => 'Privilege Leave', 'code' => 'PL', 'days' => 15, 'is_paid' => 0])
            ->assertSessionHasNoErrors();
        $this->assertEquals(15, $type->fresh()->days);
        $this->assertFalse($type->fresh()->is_paid);

        $this->delete(route('attendance.leave.types.destroy', $type))->assertSessionHas('status');
        $this->assertSoftDeleted($type);
    }

    public function test_holiday_list_and_company_holidays(): void
    {
        $this->post(route('attendance.leave.holiday-list.store'), ['name' => 'Diwali', 'start_date' => '2026-11-08', 'end_date' => '2026-11-09'])->assertSessionHasNoErrors();
        $this->post(route('attendance.leave.holiday-list.store'), ['name' => 'Bad', 'start_date' => '2026-11-09', 'end_date' => '2026-11-08'])->assertSessionHasErrors('end_date');
        $diwali = Holiday::sole();
        $this->assertSame(2, $diwali->total_days);

        $this->get(route('attendance.leave.holiday-list', ['year' => 2026]))->assertSeeInOrder(['Diwali', '2026-11-08', '2026-11-09', '2']);
        $this->get(route('attendance.leave.holiday-list', ['year' => 2025]))->assertDontSee('Diwali');

        $this->post(route('attendance.leave.holidays.store'), ['company_id' => $this->company->id, 'holidays' => [$diwali->uuid]])->assertSessionHasNoErrors();
        $this->assertTrue($this->company->holidays()->whereKey($diwali->id)->exists());
        $this->get(route('attendance.leave.holidays', ['company' => $this->company->uuid, 'year' => 2026]))->assertSeeInOrder(['Holiday list', 'Diwali', '2026-11-08']);

        // A holiday a company observes can't be deleted from the master list.
        $this->delete(route('attendance.leave.holiday-list.destroy', $diwali))->assertSessionHas('error');

        $this->delete(route('attendance.leave.holidays.destroy', [$this->company, $diwali]))->assertSessionHas('status');
        $this->assertFalse($this->company->holidays()->exists());
        $this->delete(route('attendance.leave.holiday-list.destroy', $diwali))->assertSessionHas('status');
        $this->assertSoftDeleted($diwali);
    }
}
