<?php

/*
|--------------------------------------------------------------------------
| Attendance options
|--------------------------------------------------------------------------
|
| Shift structure and pay-rule choices from the old HRMS's Configs ›
| Attendance page. Keys are stored; values are shown.
|
*/

return [
    // Each shift type, how many a company may configure, and its timed parts
    // keyed by the code used on rosters and in the monthly grid.
    'shift_types' => [
        'open' => ['label' => 'Open Shift', 'slots' => 1, 'parts' => ['GS' => null]],
        'general' => ['label' => 'General Shift', 'slots' => 4, 'parts' => ['GS' => null]],
        'day_night' => ['label' => 'Day & Night Shift (12 Hour Rotational)', 'slots' => 1, 'parts' => ['DS' => 'Day Shift Timing', 'NS' => 'Night Shift Timing']],
        'rotational' => ['label' => 'Rotational Shift', 'slots' => 4, 'parts' => ['FS' => 'First', 'SS' => 'Second', 'NS' => 'Night']],
    ],

    'part_names' => [
        'GS' => 'General Shift',
        'DS' => 'Day Shift',
        'FS' => 'First Shift',
        'SS' => 'Second Shift',
        'NS' => 'Night Shift',
    ],

    'half_day_rules' => [
        'slab' => 'Below 2 hour - unpaid day, Between 2 to 6 hour - half day, Above 6 hour - full paid day',
        'hourly' => 'Hourly paid',
    ],

    'wo_ph_work_pay' => ['coff' => 'COFF (compensatory off)', 'ot' => 'OT (overtime)'],

    'effect_on' => ['basic' => 'On Basic Salary', 'gross' => 'On Gross Salary'],

    'ot_rates' => ['regular' => 'Regular Salary', 'double' => 'Double Of Regular Salary'],

    // The rule questions on each shift's "Shift Settings", in order.
    'rules' => [
        'half_day_rule' => ['How do you want to Manage Employee Half Day ?', 'half_day_rules', null],
        'wo_ph_work_pay' => ['How do you want to Pay on WeekOff & Holiday if Employee worked ?', 'wo_ph_work_pay', null],
        'pay_daily_rate_on_wo_ph' => ['Do you want to pay Daily Rate Employees for WeekOff and Holidays ?', 'yes_no', null],
        'ot_effect_on' => ['How do you want to pay for Employee Overtime hours ?', 'effect_on', 'Effect On'],
        'ot_rate' => [null, 'ot_rates', 'Overtime Salary'],
        'ph_ot_effect_on' => ['How do you want to pay for Employee Public Holiday Overtime hours ?', 'effect_on', 'Effect On'],
        'ph_ot_rate' => [null, 'ot_rates', 'Overtime Salary'],
        'ot_after_hours' => ['Calculate Employee Overtime hour after ?', 'number', 'How many Working hours'],
        'late_penalty_after_minutes' => ['Do you want to deduct penalty for Employee late coming ? (Half Day Penalty)', 'number', 'After how many Minutes Half Day Penalty apply'],
        'pf_effect_on' => ['How do you want to calculate Employee PF Deduction ?', 'effect_on', null],
        'esi_effect_on' => ['How do you want to calculate Employee ESI Deduction ?', 'effect_on', null],
    ],
];
