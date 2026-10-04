import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * SRYMPEX HRMS design tokens.
 *
 * - Theme: red, white and black (SRYMPEX). brand is the red, for primary
 *   actions, links, focus rings and the active navigation; the sidebar is
 *   black and pages are white.
 * - Status colours keep their meaning only: emerald = success,
 *   amber = warning, red = danger. Neutrals are Tailwind's gray.
 * - Type scale (15px body, as in the old system): xs 12 · sm 13.5 · base 15 ·
 *   lg 17 · xl 20 · 2xl 24 · 3xl 28 (page titles) · 4xl 34 (figures).
 * - Radius: controls 8px (md), cards 12px (lg), dialogs 16px (xl).
 * - Shadows: sm for cards, md for menus and toasts, lg/xl for dialogs.
 */

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Funnel Display"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#fff1f2',
                    100: '#ffe1e3',
                    200: '#ffc7cb',
                    300: '#ff9ca3',
                    400: '#fb6170',
                    500: '#ef3343',
                    600: '#d4141f',
                    700: '#b20f19',
                    800: '#93111a',
                    900: '#7a141b',
                    950: '#43050a',
                },
            },
            fontSize: {
                xs: ['12px', { lineHeight: '16px' }],
                sm: ['13.5px', { lineHeight: '20px' }],
                base: ['15px', { lineHeight: '22px' }],
                lg: ['17px', { lineHeight: '24px' }],
                xl: ['20px', { lineHeight: '28px' }],
                '2xl': ['24px', { lineHeight: '32px' }],
                '3xl': ['28px', { lineHeight: '34px' }],
                '4xl': ['34px', { lineHeight: '40px' }],
            },
            borderRadius: {
                DEFAULT: '6px',
                md: '8px',
                lg: '12px',
                xl: '16px',
            },
            boxShadow: {
                sm: '0 1px 2px 0 rgb(16 24 40 / 0.05)',
                DEFAULT: '0 1px 3px 0 rgb(16 24 40 / 0.08), 0 1px 2px -1px rgb(16 24 40 / 0.06)',
                md: '0 6px 16px -4px rgb(16 24 40 / 0.12), 0 2px 6px -2px rgb(16 24 40 / 0.06)',
                lg: '0 14px 30px -8px rgb(16 24 40 / 0.18), 0 4px 10px -4px rgb(16 24 40 / 0.06)',
                xl: '0 24px 48px -12px rgb(16 24 40 / 0.25)',
            },
            keyframes: {
                'progress-indeterminate': {
                    '0%': { transform: 'translateX(-100%)' },
                    '100%': { transform: 'translateX(250%)' },
                },
                shimmer: {
                    '100%': { transform: 'translateX(100%)' },
                },
            },
            animation: {
                'progress-indeterminate': 'progress-indeterminate 1.1s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
