<?php

/*
|--------------------------------------------------------------------------
| Nested tabs
|--------------------------------------------------------------------------
|
| Tabs shown inside a section tab, keyed by that tab's route name. The
| landing tab ('') uses the parent's route name; the others are
| "{parent}.{key}".
|
*/

return [
    'attendance.leave' => [
        '' => 'Leaves',
        'types' => 'Leave type',
        'balance' => 'Leave balance',
        'holiday-list' => 'Holiday list',
        'holidays' => 'Holiday',
    ],
];
