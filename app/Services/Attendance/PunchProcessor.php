<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Works out, for an employee's punches, which attendance day each counts
 * towards, which part of their shift it was (GS, DS, FS, SS or NS), whether
 * it was a night shift, and whether its matching in/out punch is missing.
 * Locked punches keep their values but still pair with their neighbours.
 */
class PunchProcessor
{
    /** An out punch more than this long after an in punch doesn't close it. */
    private const MAX_SHIFT_HOURS = 20;

    public function process(Employee $employee, CarbonInterface $from, CarbonInterface $to): void
    {
        $from = CarbonImmutable::parse($from)->startOfDay()->subDay();
        $to = CarbonImmutable::parse($to)->startOfDay()->addDays(2);
        $shift = $employee->shift;

        $punches = $employee->attendances()
            ->whereBetween('punched_at', [$from, $to])
            ->orderBy('punched_at')->orderBy('id')
            ->get();

        $open = null;

        foreach ($punches as $punch) {
            if ($punch->isIn()) {
                if ($open) {
                    $this->fill($open, ['is_missing' => true]);
                }

                $code = $this->nearestPart($shift, $punch->punched_at, 'start');
                $this->fill($punch, [
                    'work_date' => $punch->punched_at->toDateString(),
                    'shift_id' => $punch->shift_id ?? $shift?->id,
                    'shift_code' => $code,
                    'is_night_shift' => $this->isNight($shift, $code),
                    'is_missing' => true, // until an out punch closes it
                ]);
                $open = $punch;

                continue;
            }

            if ($open && $open->punched_at->diffInHours($punch->punched_at) <= self::MAX_SHIFT_HOURS) {
                $this->fill($punch, [
                    'work_date' => $open->work_date->toDateString(),
                    'shift_id' => $punch->shift_id ?? $open->shift_id,
                    'shift_code' => $open->shift_code,
                    'is_night_shift' => $open->is_night_shift || ! $punch->punched_at->isSameDay($open->punched_at),
                    'is_missing' => false,
                ]);
                $this->fill($open, ['is_missing' => false]);
                $open = null;

                continue;
            }

            $code = $this->nearestPart($shift, $punch->punched_at, 'end');
            $this->fill($punch, [
                'work_date' => $punch->punched_at->toDateString(),
                'shift_id' => $punch->shift_id ?? $shift?->id,
                'shift_code' => $code,
                'is_night_shift' => $this->isNight($shift, $code),
                'is_missing' => true,
            ]);
        }

        $punches->each(fn (Attendance $punch) => $punch->isDirty() && $punch->save());
    }

    /**
     * The shift part whose start (or end) time is closest to $time, going
     * round the clock so 23:00 is near 00:30.
     */
    public function nearestPart(?Shift $shift, CarbonInterface $time, string $edge): ?string
    {
        if (! $shift || empty($shift->timings)) {
            return null;
        }

        $minutes = $time->hour * 60 + $time->minute;

        return collect($shift->timings)
            ->filter(fn ($part) => filled($part[$edge] ?? null))
            ->sortBy(function ($part) use ($minutes, $edge) {
                [$h, $m] = array_map('intval', explode(':', $part[$edge]));
                $diff = abs($minutes - ($h * 60 + $m));

                return min($diff, 1440 - $diff);
            })
            ->keys()
            ->first();
    }

    private function isNight(?Shift $shift, ?string $code): bool
    {
        if (! $shift || ! $code) {
            return false;
        }

        $part = $shift->timings[$code] ?? null;

        return $code === 'NS' || ($part && ($part['end'] ?? '') !== '' && $part['end'] <= $part['start']);
    }

    private function fill(Attendance $punch, array $values): void
    {
        if (! $punch->is_locked) {
            $punch->fill($values);
        }
    }
}
