import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * SRYMPEX HRMS design tokens ("Register").
 *
 * - Paper (#F5F7FA) pages, white cards, Ink (#131722) text, Slate (#566074)
 *   secondary text, Cobalt (#2445A8) for buttons, links, selection and focus.
 *   Srympex Red is the logo only, so red always means "danger".
 * - Every colour is a CSS variable (resources/css/app.css), so pages follow the
 *   theme and dark mode. `brand` (legacy scale) maps to Cobalt, `gray` to Slate.
 * - Type: Instrument Sans (headings + interface) and IBM Plex Mono (IDs, amounts,
 *   dates). Scale: xs 12 · 13 · sm 14 · base 15 · lg 17 · xl 20 · 2xl 24 · 3xl 30.
 * - Spacing: Tailwind's 4px grid (1 = 4px). Radius: controls 6px, cards 8px, dialogs 12px.
 * - Motion: 150-250ms ease-out on hover/open/close only (`duration-fast/base/slow`).
 */

const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;
const scale = (prefix) => Object.fromEntries([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map((n) => [n, token(`${prefix}-${n}`)]));

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
                sans: ['"Instrument Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Legacy scale used by older markup: now Cobalt (flips in dark mode).
                brand: scale('brand'),
                // Srympex Red: the logo only.
                srympex: { DEFAULT: token('brand'), soft: token('brand-soft'), ink: token('brand-ink') },
                gray: scale('gray'),
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
                '2xs': ['11px', { lineHeight: '14px' }],
                xs: ['12px', { lineHeight: '16px' }],
                13: ['13px', { lineHeight: '18px' }],
                sm: ['14px', { lineHeight: '20px' }],
                base: ['15px', { lineHeight: '22px' }],
                lg: ['17px', { lineHeight: '24px' }],
                xl: ['20px', { lineHeight: '28px' }],
                '2xl': ['24px', { lineHeight: '30px', letterSpacing: '-0.015em' }],
                '3xl': ['30px', { lineHeight: '36px', letterSpacing: '-0.02em' }],
                '4xl': ['36px', { lineHeight: '42px', letterSpacing: '-0.02em' }],
            },
            spacing: {
                control: 'var(--control-h)',
                'control-sm': 'var(--control-h-sm)',
            },
            borderRadius: {
                DEFAULT: '5px',
                md: 'var(--radius-control)',
                lg: 'var(--radius-card)',
                xl: 'var(--radius-dialog)',
                control: 'var(--radius-control)',
                card: 'var(--radius-card)',
                dialog: 'var(--radius-dialog)',
            },
            boxShadow: {
                sm: 'none',
                DEFAULT: 'none',
                hairline: 'none',
                // Raised layers only: menus, pop-overs, dialogs, toasts.
                md: '0 1px 2px rgb(var(--shadow) / 0.06), 0 8px 24px -10px rgb(var(--shadow) / 0.18)',
                pop: '0 1px 2px rgb(var(--shadow) / 0.06), 0 12px 32px -10px rgb(var(--shadow) / 0.22)',
                lg: '0 1px 2px rgb(var(--shadow) / 0.06), 0 20px 48px -16px rgb(var(--shadow) / 0.28)',
                xl: '0 24px 56px -16px rgb(var(--shadow) / 0.32)',
                bar: '0 -1px 0 rgb(var(--line)), 0 -8px 24px -12px rgb(var(--shadow) / 0.14)',
            },
            transitionDuration: {
                fast: '150ms',
                base: '200ms',
                slow: '250ms',
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
