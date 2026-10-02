<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\ShiftRosterEntry;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Which days an employee is expected to work and on which shift part: their
 * employment period, their company's holidays, and their weekly off — from
 * the month's shift roster when they're on one, otherwise their usual
 * weekly leave day.
 */
class WorkCalendar
{
    /** @var array<string, Collection<string, Holiday>> */
    private array $holidays = [];

    /** @var array<string, ?array<string, string>> employee id + month => roster days */
    private array $rosters = [];

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
        $rostered = $this->rosterCode($employee, $date);

        if ($rostered !== null) {
            return $rostered === 'WO';
        }

        return $employee->weekly_leave_day !== null && strtolower($date->englishDayOfWeek) === $employee->weekly_leave_day;
    }

    /**
     * The shift code (or WO) the roster gives the employee that day, if
     * they're on a roster that month.
     */
    public function rosterCode(Employee $employee, CarbonInterface $date): ?string
    {
        $key = $employee->id.':'.$date->format('Y-m');

        if (! array_key_exists($key, $this->rosters)) {
            $this->rosters[$key] = ShiftRosterEntry::where('employee_id', $employee->id)
                ->whereHas('roster', fn ($q) => $q->whereDate('month', $date->copy()->startOfMonth()))
                ->value('days');
            $this->rosters[$key] = is_string($this->rosters[$key]) ? json_decode($this->rosters[$key], true) : $this->rosters[$key];
        }

        return $this->rosters[$key][(string) $date->day] ?? null;
    }
}
