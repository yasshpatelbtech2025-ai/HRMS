<?php

/*
|--------------------------------------------------------------------------
| Compliance › Forms
|--------------------------------------------------------------------------
|
| The acts shown as buttons on the Forms page, in the old system's order,
| and the forms listed under each. A form with a "view" opens its register
| page (print and PDF); the others are listed but not built yet.
|
*/

return [
    'acts' => [
        'contract-labour-act' => [
            'label' => 'CONTRACT LABOUR ACT 1970',
            'title' => 'CONTRACT LABOUR (REGULATION and ABOLITION) ACT 1970',
            'forms' => [
                'form-a' => ['title' => 'Form A - Register of workman employed by contractor', 'view' => 'form-a'],
                'form-b' => ['title' => 'Form B - Register of wages / Register of Overtime', 'view' => 'form-b'],
                'form-c' => ['title' => 'Form C - Register of Deduction for Damages or loss / Register of Fines / Register of Advances', 'view' => 'form-c'],
                'form-d' => ['title' => 'Form D - Muster roll', 'view' => 'form-d'],
                'form-e' => ['title' => 'Form E - Leave with Wage Register'],
                'form-14' => ['title' => 'Form 14 - Employment Card', 'view' => 'form-14'],
                'form-19' => ['title' => 'Form 19 - Wage Slip', 'link' => 'payroll.salaries'],
                'bonus-form-d' => ['title' => 'Form D - Annual Return - Bonus Paid To Employees For The Accounting Year', 'view' => 'bonus-form-d'],
                'form-25-half-yearly' => ['title' => 'Form A - Half-yearly return', 'view' => 'form-25'],
                'form-25-annual' => ['title' => 'FORM 25 - Annual Return - Labour Office'],
                'form-vi' => ['title' => 'Form VI - Copy of Labour License'],
                'gratuity-form-f' => ['title' => 'Form F - The Payment of Gratuity Act, 1972', 'view' => 'gratuity-form-f'],
                'form-13' => ['title' => 'FORM 13 - REGISTER OF WORKMEN', 'view' => 'form-13'],
                'form-16' => ['title' => 'FORM 16 - ATTENDANCE REGISTER', 'view' => 'form-16'],
                'form-17' => ['title' => 'FORM 17 - WAGE REGISTER', 'view' => 'form-17'],
                'form-18' => ['title' => 'FORM 18 - LEAVE REGISTER', 'view' => 'form-18'],
                'form-20' => ['title' => 'FORM 20 - REGISTER OF DEDUCATION FOR DAMAGES OR LOSS', 'view' => 'form-20'],
                'form-21' => ['title' => 'FORM 21 - REGISTER OF FINES', 'view' => 'form-21'],
                'form-22' => ['title' => 'FORM 22 - ADVANCE REGISTER', 'view' => 'form-22'],
                'form-23' => ['title' => 'FORM 23 - OVERTIME REGISTER', 'view' => 'form-23'],
                'form-xxv' => ['title' => 'FORM XXV - YEARLY RETURN'],
                'form-i' => ['title' => 'FORM I - APPLICATION FOR REGISTRATION OF ESTABLISHMENT'],
                'form-iv' => ['title' => 'FORM IV - APPLICATION FOR LICENCE'],
                'form-v' => ['title' => 'FORM V - CERTIFICATE BY PRINCIPAL EMPLOYER'],
                'form-vii' => ['title' => 'FORM VII - APPLICATION FOR LICENCE RENEWAL'],
                'form-v-licence' => ['title' => 'FORM V - LICENCE APPLY'],
            ],
        ],
        'factory-act' => ['label' => 'FACTORY ACT 1948', 'title' => 'FACTORY ACT 1948', 'forms' => []],
        'employment-exchange-act' => ['label' => 'EMPLOYMENT EXCHANGE ACT 1959', 'title' => 'EMPLOYMENT EXCHANGE ACT 1959', 'forms' => []],
        'payment-of-bonus-act' => ['label' => 'PAYMENT OF BONUS ACT 1965', 'title' => 'PAYMENT OF BONUS ACT 1965', 'forms' => []],
        'pt' => ['label' => 'PT ACT 1976', 'title' => 'PT ACT 1976', 'forms' => []],
        'pf' => ['label' => 'PF ACT 1952', 'title' => 'PF ACT 1952', 'forms' => []],
        'wc' => ['label' => 'WC ACT 1923', 'title' => 'WC ACT 1923', 'forms' => []],
        'labour-act' => ['label' => 'LABOUR WALFARE FUND ACT', 'title' => 'LABOUR WELFARE FUND ACT', 'forms' => []],
        'esic' => ['label' => 'ESIC ACT 1948', 'title' => 'ESIC ACT 1948', 'forms' => []],
        'maternity' => ['label' => 'MATERNITY ACT 1961', 'title' => 'MATERNITY ACT 1961', 'forms' => []],
        'verification' => ['label' => 'POLICE VERIFICATION', 'title' => 'POLICE VERIFICATION', 'forms' => []],
    ],

    // Hand-entered register rows: Form C (all recovery types), 20 (damage or loss) and 21 (fines).
    'recovery_types' => ['Damage', 'Loss', 'Fines', 'Advance', 'Loans', 'Absence'],
];
