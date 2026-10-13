<?php

/*
| Sidebar, breadcrumbs and the command palette are all built from this list.
|
| item: label, route, icon, match (route patterns that mark it active),
|       keywords (extra palette search terms), children (sub-menu items).
| Items whose module is not built yet point at placeholder routes.
*/
return [
    [
        'label' => null,
        'items' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'keywords' => 'home overview attention'],
        ],
    ],
    [
        'label' => 'Workforce',
        'items' => [
            ['label' => 'Companies', 'route' => 'company.index', 'icon' => 'building', 'match' => ['company.*'], 'keywords' => 'clients establishments'],
            ['label' => 'Employees', 'route' => 'employees.index', 'icon' => 'users', 'match' => ['employees.*', 'departments.*', 'positions.*'], 'children' => [
                ['label' => 'Employee list', 'route' => 'employees.index', 'match' => ['employees.index', 'employees.create', 'employees.edit'], 'keywords' => 'staff workers people'],
                ['label' => 'Departments', 'route' => 'departments.index', 'match' => ['departments.*']],
                ['label' => 'Designations', 'route' => 'positions.index', 'match' => ['positions.*'], 'keywords' => 'positions roles'],
                ['label' => 'Separated', 'route' => 'employees.separated', 'match' => ['employees.separated'], 'keywords' => 'left resigned exit'],
            ]],
        ],
    ],
    [
        'label' => 'Time',
        'items' => [
            ['label' => 'Attendance', 'route' => 'attendance.index', 'icon' => 'clock', 'match' => ['attendance.*'], 'children' => [
                ['label' => 'Monthly attendance', 'route' => 'attendance.index', 'match' => ['attendance.index'], 'keywords' => 'muster grid'],
                ['label' => 'Missing punch', 'route' => 'attendance.missing-punch'],
                ['label' => 'Requests', 'route' => 'attendance.requests', 'keywords' => 'approvals regularisation'],
                ['label' => 'Leave', 'route' => 'attendance.leave'],
                ['label' => 'Shifts', 'route' => 'attendance.shifts'],
                ['label' => 'Holidays', 'route' => 'attendance.holidays'],
            ]],
        ],
    ],
    [
        'label' => 'Compliance',
        'items' => [
            ['label' => 'Compliance', 'route' => 'compliance.index', 'icon' => 'scale', 'match' => ['compliance.*'], 'keywords' => 'pf esic pt lwf returns challan'],
            ['label' => 'Documents', 'route' => 'documents.index', 'icon' => 'document', 'match' => ['documents.*'], 'keywords' => 'files expiry'],
        ],
    ],
    [
        'label' => 'Money',
        'items' => [
            ['label' => 'Payroll', 'route' => 'payroll.index', 'icon' => 'wallet', 'match' => ['payroll.*'], 'keywords' => 'salary payslip wages'],
        ],
    ],
    [
        'label' => 'Insights',
        'items' => [
            ['label' => 'Reports', 'route' => 'reports.index', 'icon' => 'chart', 'match' => ['reports.*']],
        ],
    ],
    [
        'label' => 'Admin',
        'items' => [
            ['label' => 'Settings', 'route' => 'configs.index', 'icon' => 'cog', 'match' => ['configs.*'], 'keywords' => 'configs configuration'],
        ],
    ],
];
