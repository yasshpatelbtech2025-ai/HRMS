<?php

// Product name and logo mark. Change BRAND_NAME in .env (or the defaults
// here) and every page, the browser tab and the login screen follow.
return [
    'name' => env('BRAND_NAME', env('APP_NAME', 'HRMS')),

    // Shown under the name in the sidebar and on the login screen.
    'tagline' => env('BRAND_TAGLINE', 'Manpower & payroll'),

    // One or two letters inside the logo tile.
    'mark' => env('BRAND_MARK', 'H'),
];
