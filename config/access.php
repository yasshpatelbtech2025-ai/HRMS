<?php

/*
|--------------------------------------------------------------------------
| Roles and access
|--------------------------------------------------------------------------
|
| Admins see and do everything. Users see only the companies assigned to
| them in Company › User access, and cannot delete. Seats are the active
| accounts allowed per role.
|
*/

return [
    'seats' => ['admin' => 2, 'user' => 10],

    // Routes (by name) only admins can open; their tabs are hidden from users.
    // A whole section (e.g. 'invoice*') hides it from the sidebar too.
    'admin_only' => [
        'company.firm*', 'company.id-card-formats*', 'company.devices', 'company.user-access*', 'company.store', 'company.update',
        'company.destroy', 'configs.recently-deleted', 'recently-deleted.*', 'configs.activity-log*', 'invoice*',
        // The holiday master list is shared by every company.
        'attendance.leave.holiday-list.store', 'attendance.leave.holiday-list.update',
    ],

    // Configs › Activity log entries older than this are removed (daily model:prune).
    'activity_retention_days' => 730,
];
