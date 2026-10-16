<?php

/*
|--------------------------------------------------------------------------
| Section sub-tabs
|--------------------------------------------------------------------------
|
| Each sidebar section and the tabs shown across the top of its pages, in
| order. Keys are the URI segment after the section ('' is the section's
| landing tab). A tab's route is named "{section}.{key}", or
| "{section}.index" for the landing tab. Tabs without a real controller
| yet are served by a placeholder page (see routes/web.php).
|
*/

return [
    'company' => [
        '' => 'Company',
        'devices' => 'Devices',
        'firm' => 'Firm',
        'id-card-formats' => 'ID Card Formats',
        'user-access' => 'User access',
    ],

    'attendance' => [
        '' => 'Monthly attendance',
        'missing-punch' => 'Missing punch',
        'requests' => 'Attendance requests',
        'shifts' => 'Shift management',
        'raw-data' => 'Attendance raw data',
        'smart' => 'Smart attendance',
        'leave' => 'Leave',
    ],

    'employees' => [
        '' => 'Employee',
        'departments' => 'Department',
        'positions' => 'Positions',
        'id-cards' => 'Employee id card',
        'separations' => 'Separation employees list',
    ],

    'compliance' => [
        'to-do' => 'To Do List',
        '' => 'Forms',
    ],

    'documents' => [
        '' => 'Documents',
    ],

    'payroll' => [
        '' => 'Salary Generate',
        'salaries' => 'Manage Employee Salary',
        'advances' => 'Salary Advance',
        'adjustments' => 'Salary adjustments',
        'revisions' => 'Salary revision',
        'current' => 'Current Salary',
        'loans' => 'Loan',
        'tds' => 'TDS',
        'bonus' => 'Bonus',
    ],

    // Admin-only (config/access.php).
    'invoice' => [
        '' => 'Invoices',
    ],

    'reports' => [
        '' => 'Attendance sheet',
        'attendance' => 'Attendance report',
        'lateness' => 'Lateness closing attendance report',
        'attendance-log' => 'Attendance log',
        'daily-present' => 'Daily present',
        'employees' => 'Employee report',
    ],

    'configs' => [
        '' => 'Attendance',
        'payroll' => 'Payroll',
        'recently-deleted' => 'Recently deleted',
        'activity-log' => 'Activity log',
    ],
];
