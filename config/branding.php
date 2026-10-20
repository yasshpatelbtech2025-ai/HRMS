<?php

// Product name and logo, in one place. Every screen, the login page and the error pages
// read these. To use a real logo: put the file in public/ (e.g. public/images/logo.svg)
// and set BRAND_LOGO=images/logo.svg in .env. Defaults to the Srympex wordmark (public/images/brand);
// leave BRAND_LOGO empty for that, or set BRAND_LOGO=none to show the letter tile instead.
return [
    'name' => env('BRAND_NAME', 'SRYMPEX'),

    // Shown under the name in the sidebar and on the login screen.
    'tagline' => env('BRAND_TAGLINE', 'Manpower & payroll'),

    // Letter(s) in the placeholder tile.
    'mark' => env('BRAND_MARK', 'S'),

    // Path under public/; empty = the Srympex wordmark, 'none' = the letter tile.
    'logo' => match ($logo = env('BRAND_LOGO')) {
        null, '' => 'images/brand/srympex-logo.png',
        'none' => null,
        default => $logo,
    },
];
