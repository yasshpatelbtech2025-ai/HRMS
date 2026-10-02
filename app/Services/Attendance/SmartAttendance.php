<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * "Smart attendance": marks an employee present for every working day of a
 * month that has no punches yet, punching in and out at their shift times.
 * Week-offs, holidays and days outside their employment are left alone.
 */
class SmartAttendance
{
    public function __construct(private PunchProcessor $processor, private WorkCalendar $calendar) {}

    /**
     * @return int days filled
     */
    public function fill(Employee $employee, int $year, int $month): int
    {
        $shift = $employee->shift;

        if (! $shift || empty($shift->timings)) {
            return 0;
        }

        $first = CarbonImmutable::create($year, $month, 1);
        $last = $first->endOfMonth()->startOfDay()->min(CarbonImmutable::today());

        if ($last->lt($first)) {
            return 0;
        }

        $holidays = $this->calendar->holidays($employee->company_id, $first, $last);
        $punched = $employee->attendances()->whereDate('work_date', '>=', $first)->whereDate('work_date', '<=', $last)
            ->pluck('work_date')->map->toDateString()->flip();
        $code = array_key_first($shift->timings);
        $timing = $shift->timings[$code];
        $filled = 0;

        DB::transaction(function () use ($employee, $shift, $first, $last, $holidays, $punched, $code, $timing, &$filled) {
            for ($day = $first; $day->lte($last); $day = $day->addDay()) {
                $date = $day->toDateString();

                if (isset($punched[$date]) || $holidays->has($date)
                    || ! $this->calendar->isEmployed($employee, $day) || $this->calendar->isWeeklyOff($employee, $day)) {
                    continue;
                }

                $in = $day->setTimeFromTimeString($timing['start']);
                $out = $day->setTimeFromTimeString($timing['end']);
                if ($out->lte($in)) {
                    $out = $out->addDay();
                }

                foreach ([Attendance::IN => $in, Attendance::OUT => $out] as $type => $time) {
                    $employee->attendances()->create([
                        'company_id' => $employee->company_id, 'punched_at' => $time, 'work_date' => $date, 'type' => $type,
                        'source' => 'smart', 'shift_id' => $shift->id, 'shift_code' => $code,
                    ]);
                }
                $filled++;
            }

            $this->processor->process($employee, $first, $last);
        });

        return $filled;
    }
}
