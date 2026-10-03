<?php

/*
|--------------------------------------------------------------------------
| Income tax (TDS on salary)
|--------------------------------------------------------------------------
|
| The slabs payroll uses to estimate each employee's yearly tax, by regime
| (Employee › tax regime). Amounts are per financial year (April - March),
| as set by the Finance Act 2025 for FY 2025-26 onwards; update them here
| when a budget changes them.
|
*/

return [
    'standard_deduction' => ['new' => 75000, 'old' => 50000],

    // [upper limit of the slab (null = no limit), rate %]
    'slabs' => [
        'new' => [[400000, 0], [800000, 5], [1200000, 10], [1600000, 15], [2000000, 20], [2400000, 25], [null, 30]],
        'old' => [[250000, 0], [500000, 5], [1000000, 20], [null, 30]],
    ],

    // Section 87A: no tax up to the limit (rebate up to max); the new regime
    // also gives marginal relief just above it.
    'rebate' => ['new' => ['limit' => 1200000, 'max' => 60000], 'old' => ['limit' => 500000, 'max' => 12500]],

    'cess' => 4, // health and education cess, % of tax

    // Old regime: employee PF / VPF count under section 80C up to this.
    'section_80c' => 150000,
];
