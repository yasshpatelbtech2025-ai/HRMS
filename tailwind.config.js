import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/*
 * SRYMPEX HRMS design tokens ("Ledger").
 *
 * - A calm work tool: warm off-white pages, white cards, near-black ink and a
 *   deep teal for primary actions, links, focus and the active navigation.
 *   Colour is otherwise kept for meaning: emerald = success, amber = warning,
 *   red = danger. The SRYMPEX logo keeps its own red (components/brand-logo).
 * - `brand` is the teal and `gray` the warm neutral scale, so every existing
 *   page picks up the theme. Newer components use the semantic names
 *   (canvas, surface, ink, line, primary...), backed by CSS variables in
 *   resources/css/app.css.
 * - Type: IBM Plex Sans (UI) and IBM Plex Mono (IDs, numbers), self-hosted.
 *   Scale: xs 12 · sm 13.5 · base 15 · lg 17 · xl 20 · 2xl 24 · 3xl 28 · 4xl 34.
 * - Radius: controls 6px (md), cards 10px (lg), dialogs 14px (xl).
 * - Shadows are hairline for cards; md/lg only for menus, toasts and dialogs.
 */

const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

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
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    50: '#effaf7',
                    100: '#d9f0ea',
                    200: '#b3e0d5',
                    300: '#82cabb',
                    400: '#4fae9c',
                    500: '#2a9080',
                    600: '#0b6b5e',
                    700: '#095a4f',
                    800: '#0a4941',
                    900: '#0b3c36',
                    950: '#04221f',
                },
                // Warm neutrals: surfaces stay paper-like, text stays crisp.
                gray: {
                    50: '#f7f7f4',
                    100: '#f0f0ec',
                    200: '#e4e4df',
                    300: '#cfcfc8',
                    400: '#a3a49f',
                    500: '#6b6e76',
                    600: '#4a4d55',
                    700: '#373a41',
                    800: '#25272d',
                    900: '#16181d',
                    950: '#0d0e11',
                },
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
