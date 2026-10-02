<?php

namespace App\Services\Attendance;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Collection;

/**
 * Each employee's allowance, use and balance of each leave type in a year.
 * The allowance is the leave type's days unless overridden; use is the
 * approved leave starting that year plus any manual adjustment.
 */
class LeaveBalances
{
    /**
     * @param  Collection<int, int>  $employeeIds
     * @param  Collection<int, LeaveType>  $types
     * @return array<int, array<int, array{total: float, used: float, approved: float, balance: float}>> employee id => leave type id => figures
     */
    public function forYear(Collection $employeeIds, Collection $types, int $year): array
    {
        $approved = LeaveApplication::whereIn('employee_id', $employeeIds)->where('status', LeaveApplication::APPROVED)
            ->whereYear('approved_start_date', $year)
            ->selectRaw('employee_id, leave_type_id, sum(approved_days) as days')
            ->groupBy('employee_id', 'leave_type_id')->get()
            ->groupBy('employee_id')->map(fn ($rows) => $rows->pluck('days', 'leave_type_id'));
        $overrides = LeaveBalance::whereIn('employee_id', $employeeIds)->where('year', $year)->get()
            ->groupBy('employee_id')->map(fn ($rows) => $rows->keyBy('leave_type_id'));

        $result = [];

        foreach ($employeeIds as $employeeId) {
            foreach ($types as $type) {
                $override = $overrides[$employeeId][$type->id] ?? null;
                $taken = (float) ($approved[$employeeId][$type->id] ?? 0);
                $total = (float) ($override?->total ?? $type->days);
                $used = $taken + (float) ($override?->used_adjustment ?? 0);

                $result[$employeeId][$type->id] = ['total' => $total, 'used' => $used, 'approved' => $taken, 'balance' => $total - $used];
            }
        }

        return $result;
    }
}
