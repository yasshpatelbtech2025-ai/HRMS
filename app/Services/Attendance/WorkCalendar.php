<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Which days an employee is expected to work: their employment period,
 * weekly off and their company's holidays.
 */
class WorkCalendar
{
    /** @var array<string, Collection<string, Holiday>> company id + range => holidays by date */
    private array $holidays = [];

    /**
     * The company's holidays between two dates, keyed by date (Y-m-d).
     *
     * @return Collection<string, Holiday>
     */
    public function holidays(int $companyId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $key = "{$companyId}:{$from->toDateString()}:{$to->toDateString()}";

        return $this->holidays[$key] ??= Holiday::whereHas('companies', fn ($q) => $q->whereKey($companyId))
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)
            ->get()
            ->flatMap(function (Holiday $holiday) use ($from, $to) {
                $days = [];
                for ($day = CarbonImmutable::parse(max($holiday->start_date, $from)); $day->lte(min($holiday->end_date, $to)); $day = $day->addDay()) {
                    $days[$day->toDateString()] = $holiday;
                }

                return $days;
            });
    }

    public function isEmployed(Employee $employee, CarbonInterface $date): bool
    {
        return (! $employee->date_of_joining || $date->gte($employee->date_of_joining))
            && (! $employee->separation_date || $date->lt($employee->separation_date));
    }

    public function isWeeklyOff(Employee $employee, CarbonInterface $date): bool
    {
        return $employee->weekly_leave_day !== null && strtolower($date->englishDayOfWeek) === $employee->weekly_leave_day;
    }
}
