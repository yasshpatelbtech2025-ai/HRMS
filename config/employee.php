<?php

/*
|--------------------------------------------------------------------------
| Employee form options
|--------------------------------------------------------------------------
|
| Fixed choices on the Add / Edit employee form and the Employee list
| filters, matching the old HRMS. Keys are what is stored; values are
| what is shown.
|
*/

return [
    'marital_statuses' => [
        'single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed', 'other' => 'Other',
    ],

    'blood_groups' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],

    'working_places' => [
        'near_running_machinery' => 'Worker is near running machinery',
        'adjusting_running_machine' => 'Worker is adjusting/cleaning a running machine',
        'automated_equipment_only' => 'Worker operates automated equipment only',
    ],

    'job_statuses' => ['fresher' => 'Fresher', 'experience' => 'Experience'],

    'qualification_levels' => [
        '10th' => '10th',
        '12th' => '12th',
        'graduate' => 'Graduate',
        'post_graduate' => 'Post Graduate',
        'technical' => 'Technical Course (if any)',
    ],

    'family_relations' => ['mother' => 'Mother', 'father' => 'Father', 'spouse' => 'Spouse', 'child' => 'Child'],

    'address_types' => [
        'present' => 'Present Address Info',
        'permanent' => 'Permanent Address Info',
        'nominee_permanent' => 'Nominee permanent address',
        'nominee_current' => 'Nominee current address',
    ],

    'pay_frequencies' => ['daily' => 'Daily', 'hourly' => 'Hourly', 'monthly' => 'Monthly'],

    'tax_regimes' => ['new' => 'New', 'old' => 'Old'],

    'monthly_salary_calculations' => [
        'calendar_month' => 'CALENDAR MONTH — Ex: March - 31 days, April - 30 days etc (Per day salary = Salary / No. of days in month)',
        'thirty_days' => 'EVERY MONTH 30 DAYS — Ex: March - 30 days, April - 30 days etc (Per day salary = Salary / 30)',
        'exclude_weekly_offs' => 'EXCLUDE WEEKLY OFFS — Ex: Month with 31 days and 4 weekly-off will have 27 payable days (Per day salary = Salary / Payable days)',
    ],

    'weekdays' => [
        'saturday' => 'Saturday', 'sunday' => 'Sunday', 'monday' => 'Monday', 'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday',
    ],

    // Salary structure rows. Basic is always shown; the others are added
    // from the "Select Gross Elements" picker. Bonus is a % of basic.
    'gross_elements' => [
        'basic' => 'BASIC',
        'allowance' => 'ALLOWANCE',
        'da' => 'Dearness Allowance (DA)',
        'hra' => 'House Rent Allowance (HRA)',
        'conveyance' => 'CONVEYANCE',
        'medical_allowance' => 'MEDICAL ALLOWANCE',
        'special_allowance' => 'SPECIAL ALLOWANCE',
        'education_allowance' => 'EDUCATION ALLOWANCE',
        'washing_allowance' => 'WASHING ALLOWANCE',
        'consultancy_fees' => 'CONSULTANCY FEES',
        'bonus' => 'BONUS % (% of Basic)',
        'performance_bonus' => 'PERFORMANCE BONUS',
        'joining_bonus' => 'JOINING BONUS',
    ],

    // Elements counted towards gross salary (used for the ESI estimate).
    'gross_salary_elements' => [
        'basic', 'allowance', 'da', 'hra', 'conveyance', 'medical_allowance',
        'special_allowance', 'education_allowance', 'washing_allowance',
    ],

    // Deductions entered per employee; PF, ESI, Prof Tax and LWF are
    // statutory and calculated, so they are not stored here.
    'deduction_elements' => [
        'vpf' => 'Voluntary Provident Fund (VPF)',
        'notice_period_recovery' => 'NOTICE PERIOD RECOVERY',
    ],

    // Optional Employee list columns ("Select Columns") and whether each is
    // shown until a user changes it.
    'optional_columns' => [
        'email' => ['Email', false],
        'date_of_birth' => ['Date Of Birth', true],
        'designation' => ['Designation', true],
        'joining_date' => ['Joining Date', true],
        'gender' => ['Gender', false],
        'shift' => ['Shift', true],
    ],

    'countries' => [
        'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda',
        'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan', 'Bahamas',
        'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize',
        'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil',
        'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cambodia', 'Cameroon',
        'Canada', 'Cape Verde', 'Central African Republic', 'Chad', 'Chile', 'China',
        'Colombi', 'Comoros', 'Congo (Brazzaville)', 'Congo', 'Costa Rica', 'Cote d\'Ivoire',
        'Croatia', 'Cuba', 'Cyprus', 'Czech Republic', 'Denmark', 'Djibouti',
        'Dominica', 'Dominican Republic', 'East Timor (Timor Timur)', 'Ecuador', 'Egypt', 'El Salvador',
        'Equatorial Guinea', 'Eritrea', 'Estonia', 'Ethiopia', 'Fiji', 'Finland',
        'France', 'Gabon', 'Gambia, The', 'Georgia', 'Germany', 'Ghana',
        'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana',
        'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia',
        'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy', 'Jamaica',
        'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 'Korea, North',
        'Korea, South', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon',
        'Lesotho', 'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg',
        'Macedonia', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali',
        'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia',
        'Moldova', 'Monaco', 'Mongolia', 'Morocco', 'Mozambique', 'Myanmar',
        'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'New Zealand', 'Nicaragua',
        'Niger', 'Nigeria', 'Norway', 'Oman', 'Pakistan', 'Palau',
        'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland',
        'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 'Saint Kitts and Nevis',
        'Saint Lucia', 'Saint Vincent', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia',
        'Senegal', 'Serbia and Montenegro', 'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia',
        'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'Spain', 'Sri Lanka',
        'Sudan', 'Suriname', 'Swaziland', 'Sweden', 'Switzerland', 'Syria',
        'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 'Togo', 'Tonga',
        'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu', 'Uganda',
        'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan',
        'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia',
        'Zimbabwe',
    ],
];
