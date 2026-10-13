<?php

// Product name and logo, in one place. Every screen, the login page and the error pages
// read these. To use a real logo: put the file in public/ (e.g. public/images/logo.svg)
// and set BRAND_LOGO=images/logo.svg in .env. Until then a neutral placeholder tile is shown.
return [
    'name' => env('BRAND_NAME', 'SRYMPEX'),

    // Shown under the name in the sidebar and on the login screen.
    'tagline' => env('BRAND_TAGLINE', 'Manpower & payroll'),

    // Letter(s) in the placeholder tile.
    'mark' => env('BRAND_MARK', 'S'),

    // Path under public/, or null for the placeholder.
    'logo' => env('BRAND_LOGO'),
];
