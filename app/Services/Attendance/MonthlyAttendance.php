<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\CompOffUse;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\Shift;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Works out each employee's attendance status for every day of a month,
 * and the month's totals:
 *
 *  - LEAVE   approved leave covers the day
 *  - COFF    a compensatory off was taken
 *  - P / HD  punched in and out; hours decide full or half day per the
 *            shift's half-day rule, and late coming beyond the shift's
 *            penalty minutes makes a full day a half day
 *  - A       (LOP) absent on a working day, or the punch pair is incomplete
 *  - PH / WO a company holiday or the weekly off with no work
 *  - null    a future day, or outside the employee's employment
 *
 * Working on a week-off or holiday counts as P and earns overtime or a COFF,
 * depending on the shift's "Pay on WeekOff & Holiday" rule.
 */
class MonthlyAttendance
{
    public const STATUSES = ['P', 'A', 'PH', 'WO', 'HD', 'LEAVE', 'COFF'];

    public function __construct(private WorkCalendar $calendar) {}

    /**
     * @param  Collection<int, Employee>  $employees  with shift loaded
     * @return array<int, array{days: array<string, array>, totals: array<string, float>}> by employee id
     */
    public function forMonth(Collection $employees, CarbonImmutable $month): array
    {
        $first = $month->startOfMonth();
        $last = $month->endOfMonth()->startOfDay();

        return $this->forRange($employees, $first, $last);
    }

    /**
     * @return array<int, array{days: array<string, array>, totals: array<string, float>}>
     */
    public function forRange(Collection $employees, CarbonImmutable $first, CarbonImmutable $last): array
    {
        $ids = $employees->pluck('id');
        $punches = Attendance::whereIn('employee_id', $ids)
            ->whereDate('work_date', '>=', $first)->whereDate('work_date', '<=', $last)
            ->with('shift')->orderBy('punched_at')->get()
            ->groupBy(fn (Attendance $p) => $p->employee_id.'|'.$p->work_date->toDateString());
        $leaves = LeaveApplication::whereIn('employee_id', $ids)->approvedBetween($first, $last)->with('leaveType')->get()->groupBy('employee_id');
        $compOffs = CompOffUse::whereIn('employee_id', $ids)->whereDate('work_date', '>=', $first)->whereDate('work_date', '<=', $last)
            ->get()->groupBy('employee_id')->map(fn ($uses) => $uses->keyBy(fn ($u) => $u->work_date->toDateString()));
        $today = CarbonImmutable::today();

        $result = [];

        foreach ($employees as $employee) {
            $holidays = $this->calendar->holidays($employee->company_id, $first, $last);
            $days = [];
            $totals = array_fill_keys(self::STATUSES, 0.0) + ['hours' => 0.0, 'ot' => 0.0];

            for ($day = $first; $day->lte($last); $day = $day->addDay()) {
                $date = $day->toDateString();
                $leave = ($leaves[$employee->id] ?? collect())->first(fn ($l) => $day->between($l->approved_start_date, $l->approved_end_date));

                $result_day = $this->day(
                    $employee, $day, $today,
                    $punches[$employee->id.'|'.$date] ?? collect(),
                    $holidays->get($date)?->name,
                    $leave,
                    isset($compOffs[$employee->id][$date]),
                );
                $days[$date] = $result_day;

                if ($result_day['status']) {
                    $totals[$result_day['status']]++;
                    $totals['hours'] += $result_day['hours'];
                    $totals['ot'] += $result_day['ot'];
                }
            }

            $result[$employee->id] = ['days' => $days, 'totals' => $totals];
        }

        return $result;
    }

    /**
     * The status of one day.
     *
     * @param  Collection<int, Attendance>  $punches  that day's punches, in time order
     */
    public function day(Employee $employee, CarbonImmutable $day, CarbonImmutable $today, Collection $punches, ?string $holiday, ?LeaveApplication $leave, bool $compOff): array
    {
        $in = $punches->firstWhere('type', Attendance::IN);
        $out = $punches->where('type', Attendance::OUT)->last();
        $shift = $in?->shift ?? $out?->shift ?? $employee->shift;
        $weeklyOff = $this->calendar->isWeeklyOff($employee, $day);

        $base = [
            'date' => $day, 'in' => $in?->punched_at, 'out' => $out?->punched_at, 'hours' => 0.0, 'ot' => 0.0,
            'shift' => $shift?->name, 'code' => $in?->shift_code ?? $out?->shift_code,
            'missing' => $punches->contains('is_missing', true), 'locked' => $punches->contains('is_locked', true),
            'late' => false, 'offDayWork' => false, 'holiday' => $holiday, 'weeklyOff' => $weeklyOff,
            'leave' => $leave?->leaveType?->code, 'leavePaid' => (bool) $leave?->leaveType?->is_paid, 'inId' => $in?->uuid, 'outId' => $out?->uuid,
        ];

        if ($day->gt($today) || ! $this->calendar->isEmployed($employee, $day)) {
            return ['status' => null] + $base;
        }

        if ($leave) {
            return ['status' => 'LEAVE'] + $base;
        }

        if ($compOff) {
            return ['status' => 'COFF'] + $base;
        }

        if ($in && $out && ! $base['missing']) {
            $hours = round($in->punched_at->diffInMinutes($out->punched_at) / 60, 2);
            $offDay = $holiday !== null || $weeklyOff;
            $status = $this->statusForHours($shift, $hours);
            $late = $status === 'P' && ! $offDay && $this->isLate($shift, $in);

            return [
                'status' => $late ? 'HD' : $status,
                'hours' => $hours,
                'ot' => $this->overtime($shift, $hours, $offDay),
                'late' => $late,
                'offDayWork' => $offDay,
            ] + $base;
        }

        if ($punches->isEmpty() && $holiday !== null) {
            return ['status' => 'PH'] + $base;
        }

        if ($punches->isEmpty() && $weeklyOff) {
            return ['status' => 'WO'] + $base;
        }

        return ['status' => 'A'] + $base;
    }

    /**
     * Compensatory offs earned (week-off or holiday worked on a shift that
     * pays such work as COFF) less those taken, in the given year up to today.
     */
    public function compOffBalance(Employee $employee, int $year): int
    {
        if (($employee->shift?->wo_ph_work_pay ?? 'coff') !== 'coff') {
            return 0;
        }

        $first = CarbonImmutable::create($year, 1, 1);
        $last = CarbonImmutable::create($year, 12, 31)->min(CarbonImmutable::today());
        $holidays = $this->calendar->holidays($employee->company_id, $first, $last);

        $earned = $employee->attendances()->where('type', Attendance::IN)->where('is_missing', false)
            ->whereDate('work_date', '>=', $first)->whereDate('work_date', '<=', $last)
            ->pluck('work_date')->map(fn ($d) => CarbonImmutable::parse($d))->unique(fn ($d) => $d->toDateString())
            ->filter(fn (CarbonImmutable $d) => $holidays->has($d->toDateString()) || $this->calendar->isWeeklyOff($employee, $d))
            ->count();

        $used = CompOffUse::where('employee_id', $employee->id)->whereYear('work_date', $year)->count();

        return max(0, $earned - $used);
    }

    /**
     * "Below 2 hour - unpaid day, Between 2 to 6 hour - half day, Above 6
     * hour - full paid day", or "Hourly paid" (any work is a paid day).
     */
    private function statusForHours(?Shift $shift, float $hours): string
    {
        if (($shift?->half_day_rule ?? 'slab') === 'hourly') {
            return $hours > 0 ? 'P' : 'A';
        }

        return match (true) {
            $hours < 2 => 'A',
            $hours < 6 => 'HD',
            default => 'P',
        };
    }

    private function isLate(?Shift $shift, Attendance $in): bool
    {
        if (! $shift || $shift->late_penalty_after_minutes === null) {
            return false;
        }

        $start = $shift->timings[$in->shift_code ?? '']['start'] ?? null;

        if (! $start) {
            return false;
        }

        $expected = $in->work_date->setTimeFromTimeString($start);

        return $in->punched_at->gt($expected->addMinutes($shift->late_penalty_after_minutes));
    }

    /**
     * Hours beyond the shift's overtime threshold. On a week-off or holiday
     * paid as overtime, every hour worked is overtime.
     */
    private function overtime(?Shift $shift, float $hours, bool $offDay): float
    {
        if ($offDay && ($shift?->wo_ph_work_pay ?? 'coff') === 'ot') {
            return $hours;
        }

        if ($shift?->ot_after_hours === null || $hours <= (float) $shift->ot_after_hours) {
            return 0.0;
        }

        return round($hours - (float) $shift->ot_after_hours, 2);
    }
}
