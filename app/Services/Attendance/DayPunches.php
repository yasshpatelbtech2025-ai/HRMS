<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Replaces a day's punches with one in and one out time — from the monthly
 * grid's "Enter In-Time & Out-Time" or an approved attendance request.
 */
class DayPunches
{
    public function __construct(private PunchProcessor $processor) {}

    public function replace(Employee $employee, string $date, CarbonInterface $in, CarbonInterface $out, string $source = 'manual'): void
    {
        if ($in->diffInHours($out) > 20) {
            throw ValidationException::withMessages(['out_time' => __('A shift cannot be longer than 20 hours.')]);
        }

        DB::transaction(function () use ($employee, $date, $in, $out, $source) {
            $this->clear($employee, $date);

            foreach ([Attendance::IN => $in, Attendance::OUT => $out] as $type => $time) {
                $employee->attendances()->create([
                    'company_id' => $employee->company_id, 'punched_at' => $time, 'work_date' => $date,
                    'type' => $type, 'source' => $source, 'shift_id' => $employee->shift_id,
                ]);
            }

            $this->processor->process($employee, $in, $out);
        });
    }

    /**
     * Delete a day's punches, unless any are locked.
     */
    public function clear(Employee $employee, string $date): void
    {
        $punches = $employee->attendances()->whereDate('work_date', $date)->get();

        if ($punches->contains('is_locked', true)) {
            throw ValidationException::withMessages(['date' => __("This day's attendance is locked. Unlock it in Attendance raw data first.")]);
        }

        $punches->each->delete();
    }
}
