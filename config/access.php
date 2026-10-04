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
    // A future "invoice" section: add 'invoice' here (a whole section) and its routes.
    'admin_only' => [
        'company.firm*', 'company.id-card-formats*', 'company.devices', 'company.user-access*', 'company.store', 'company.update',
        'company.destroy', 'configs.recently-deleted', 'recently-deleted.*', 'invoice*',
        // The holiday master list is shared by every company.
        'attendance.leave.holiday-list.store', 'attendance.leave.holiday-list.update',
    ],

    // The sidebar is written in resources/views/layouts/sidebar.blade.php: wrap an
    // admin-only section's link (e.g. Invoice) in @can('admin') ... @endcan.
];
