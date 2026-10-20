import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * SRYMPEX HRMS design tokens ("Muster Roll").
 *
 * - Paper (#F5F6F8) pages, white cards, Site Ink (#17181C) text and primary
 *   buttons, Steel (#525A68) secondary text. Srympex Red (#DD2A1B) is kept for
 *   the logo, the active navigation marker and the dashboard muster strip.
 *   Colour is otherwise for meaning: Present Green, amber warning, crimson danger.
 * - `brand` (legacy scale) and `gray` are CSS variables, so every page follows
 *   the theme and dark mode. Newer components use the semantic names.
 * - Type: Figtree (UI, self-hosted in resources/fonts) and IBM Plex Mono (IDs, numbers).
 *   Scale: xs 12 · sm 13.5 · base 15 · lg 17 · xl 20 · 2xl 24 · 3xl 28 · 4xl 34.
 * - Radius: controls 6px (md), cards 10px (lg), dialogs 14px (xl).
 */

const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class', '.dark'],
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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Legacy scale, now the Site Ink neutrals (CSS variables, flips in dark mode).
                brand: Object.fromEntries([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map((n) => [n, token(`brand-${n}`)])),
                // Srympex Red: logo, active marker and the muster strip only.
                srympex: { DEFAULT: token('brand'), soft: token('brand-soft'), ink: token('brand-ink') },
                // Warm neutrals: surfaces stay paper-like, text stays crisp.
                // The scale is backed by CSS variables so it flips in dark mode.
                gray: Object.fromEntries([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map((n) => [n, token(`gray-${n}`)])),
                canvas: token('canvas'),
                surface: token('surface'),
                sunken: token('sunken'),
                rail: token('rail'),
                line: { DEFAULT: token('line'), strong: token('line-strong') },
                ink: { DEFAULT: token('ink'), 2: token('ink-2'), 3: token('ink-3') },
                primary: { DEFAULT: token('primary'), hover: token('primary-hover'), soft: token('primary-soft'), ink: token('primary-ink') },
                'on-primary': token('on-primary'),
                success: { DEFAULT: token('success'), soft: token('success-soft') },
                warn: { DEFAULT: token('warn'), soft: token('warn-soft') },
                danger: { DEFAULT: token('danger'), hover: token('danger-hover'), soft: token('danger-soft') },
                info: { DEFAULT: token('info'), soft: token('info-soft') },
            },
            fontSize: {
                xs: ['12px', { lineHeight: '16px' }],
                13: ['13px', { lineHeight: '18px' }],
                sm: ['13.5px', { lineHeight: '20px' }],
                base: ['15px', { lineHeight: '22px' }],
                lg: ['17px', { lineHeight: '24px' }],
                xl: ['20px', { lineHeight: '28px' }],
                '2xl': ['24px', { lineHeight: '32px' }],
                '3xl': ['28px', { lineHeight: '34px' }],
                '4xl': ['34px', { lineHeight: '40px' }],
            },
            borderRadius: {
                DEFAULT: '5px',
                md: '6px',
                lg: '10px',
                xl: '14px',
                control: '6px',
                card: '10px',
            },
            boxShadow: {
                sm: '0 1px 2px 0 rgb(22 24 29 / 0.05)',
                DEFAULT: '0 1px 2px 0 rgb(22 24 29 / 0.06)',
                hairline: '0 1px 2px 0 rgb(22 24 29 / 0.05)',
                md: '0 1px 2px rgb(22 24 29 / 0.06), 0 8px 24px -8px rgb(22 24 29 / 0.16)',
                pop: '0 1px 2px rgb(22 24 29 / 0.06), 0 12px 32px -8px rgb(22 24 29 / 0.18)',
                lg: '0 1px 2px rgb(22 24 29 / 0.06), 0 16px 40px -12px rgb(22 24 29 / 0.22)',
                xl: '0 24px 48px -12px rgb(22 24 29 / 0.25)',
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
