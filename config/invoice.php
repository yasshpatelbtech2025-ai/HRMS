<?php

/*
|--------------------------------------------------------------------------
| Invoice
|--------------------------------------------------------------------------
|
| Srympex's own details printed on every tax invoice, and the default
| rates. Tax is IGST when the customer's GST number is from another state,
| CGST + SGST (half each) when it is from Srympex's state code.
|
*/

return [
    'issuer' => [
        'name' => 'SRYMPEX STAFFING PRIVATE LIMITED',
        'signatory' => 'For Srympex Staffing Pvt Ltd',
        'logo_text' => 'Srympex',
        'state_code' => '27',
        'details' => [
            'GST No' => '27ABACS5355A1ZM',
            'BANK' => 'HDFC Bank',
            'Bank A/C No' => '50200032502492',
            'IFSC Code' => 'HDFC0000068',
            'SAC Code' => '998519',
            'MSME No' => 'MH180010372',
            'LUT No' => 'AD270326115647Y',
            'Service Category' => 'Manpower Supplier',
            'State Code' => '27',
        ],
        'branch_address' => '208-209, 7X The Business Hub, Opp. K. J. Polytechnic, Bholav, Bharuch - 390002',
        'registered_address' => '1603, Prathmesh Tower, Link Road, Near Sailee Hospital, Borivali (W), Mumbai -400091',
        'phone' => '99041 98200',
        'email' => 'contact@srympex.com',
        'website' => 'www.srympex.com',
    ],

    'service_charge_rate' => 8.5,
    'gst_rate' => 18,

    // "Employers PF Contribution @ 13%": 12% PF plus admin charges, of the PF wages.
    'pf_rate' => 13,

    'terms' => [
        'Any discrepancies should be brought to our notice within 7 days of receipt',
        'Payment within 7 days of Receipts of Invoice',
        'Input tax credit is available to a taxable person/company against this copy',
        'Certified that the particulars given above are true and correct',
    ],
];
