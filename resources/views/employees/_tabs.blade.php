<x-page-tabs :tabs="[
    'Employee' => ['employees.index', ['employees.index', 'employees.create', 'employees.edit']],
    'Department' => ['departments.index', 'departments.*'],
    'Positions' => ['positions.index', 'positions.*'],
    'Separated employees' => ['employees.separated', 'employees.separated'],
]" />
