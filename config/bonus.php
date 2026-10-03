<?php

/*
|--------------------------------------------------------------------------
| Yearly bonus (Payment of Bonus Act, 1965)
|--------------------------------------------------------------------------
*/

return [
    // Employees earning more than this a month (basic + DA) are not eligible.
    'eligibility_salary' => 21000,
    // Nor are those who worked fewer days in the accounting year.
    'eligibility_days' => 30,
    // Bonus is worked out on salary capped at this per month (or the
    // minimum wage, when higher - set it here).
    'calculation_ceiling' => 7000,
    // The statutory minimum and maximum bonus, % of that salary.
    'minimum_rate' => 8.33,
    'maximum_rate' => 20,
];
