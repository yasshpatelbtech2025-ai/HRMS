<?php

/*
|--------------------------------------------------------------------------
| Document slots
|--------------------------------------------------------------------------
|
| The documents kept on the Documents page, by category, as in the old
| HRMS. Per-company documents get one slot for each company (":company" is
| replaced by its name); the others have a single slot.
|
*/

return [
    'agreement' => [
        'title' => 'AGREEMENT',
        'short' => 'AGREEMENT',
        'per_company' => true,
        'documents' => [':company AGREEMENT'],
    ],
    'provident-fund-pf' => [
        'title' => 'PROVIDENT FUND (PF)',
        'short' => 'PF',
        'per_company' => false,
        'documents' => ['PF REGISTRATION CERTIFICATE'],
    ],
    'professional-tax-pt' => [
        'title' => 'PROFESSIONAL TAX (PT)',
        'short' => 'PT',
        'per_company' => true,
        'documents' => [':company PROFESSIONAL REGISTRATION FORM (FORM-3)'],
    ],
    'esic' => [
        'title' => 'EMPLOYEES STATE INSURANCE CORPORATION (ESIC)',
        'short' => 'ESIC',
        'per_company' => false,
        'documents' => ['ESIC REGISTRATION FORM'],
    ],
    'insurance' => [
        'title' => 'INSURANCE',
        'short' => 'INSURANCE',
        'per_company' => false,
        'documents' => ['WC POLICY DETAILS'],
    ],
];
